#!/usr/bin/env node

import { readFileSync, readdirSync } from 'node:fs'
import { join, extname, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const scriptDir = dirname(fileURLToPath(import.meta.url))
const root = join(scriptDir, '..', '..')
const storeDir = join(root, 'resources/assets/js/stores')
const routeFile = join(root, 'routes/api.base.php')

const CRITICAL_ENDPOINTS = [
  { method: 'PUT', path: 'settings/consent-legal-urls', owner: 'settings' },
  { method: 'GET', path: 'settings/google-doc-pages', owner: 'settings' },
  { method: 'PUT', path: 'settings/google-doc-pages', owner: 'settings' },
  { method: 'GET', path: 'google-doc-pages/{slug}', owner: 'settings' },
]

const HTTP_METHODS = ['get', 'post', 'put', 'patch', 'delete']
const ROUTE_METHODS = ['get', 'post', 'put', 'patch', 'delete']

const walkFiles = (dir, allowedExts) => {
  const entries = readdirSync(dir, { withFileTypes: true })
  return entries.flatMap(entry => {
    const fullPath = join(dir, entry.name)

    if (entry.isDirectory()) {
      return walkFiles(fullPath, allowedExts)
    }

    return allowedExts.has(extname(entry.name)) ? [fullPath] : []
  })
}

const extractFrontendEndpoints = () => {
  const files = walkFiles(storeDir, new Set(['.ts', '.js']))
  const endpointRegex = /http\.(get|post|put|patch|delete)\s*\(\s*(['"`])([^'"`]+)\2/g
  const endpoints = new Map()

  for (const file of files) {
    const content = readFileSync(file, 'utf8')
    let match

    while ((match = endpointRegex.exec(content)) !== null) {
      const method = match[1].toUpperCase()
      const path = match[3]
      const key = `${method} ${path}`

      if (!endpoints.has(key)) {
        endpoints.set(key, { method, path, files: new Set() })
      }

      endpoints.get(key).files.add(file.replace(`${root}/`, ''))
    }
  }

  return endpoints
}

const normalizeRoutePath = path => path.startsWith('api/') ? path.slice(4) : path

const extractBackendRoutes = () => {
  const content = readFileSync(routeFile, 'utf8')
  const routes = new Set()

  for (const method of ROUTE_METHODS) {
    const regex = new RegExp(`Route::${method}\\s*\\(\\s*'([^']+)'`, 'g')
    let match

    while ((match = regex.exec(content)) !== null) {
      routes.add(`${method.toUpperCase()} ${normalizeRoutePath(match[1])}`)
    }
  }

  return routes
}

const toRoutePattern = path => path.replace(/\$\{[^}]+\}/g, '{dynamic}')

const routeExists = (method, path, routes) => {
  const candidate = `${method} ${path}`

  if (routes.has(candidate)) {
    return true
  }

  const dynamicPattern = toRoutePattern(path)
  if (dynamicPattern !== path && routes.has(`${method} ${dynamicPattern}`)) {
    return true
  }

  return false
}

const frontendEndpoints = extractFrontendEndpoints()
const backendRoutes = extractBackendRoutes()

const criticalFailures = CRITICAL_ENDPOINTS.filter(endpoint =>
  !routeExists(endpoint.method, endpoint.path, backendRoutes),
)

const coverageRows = CRITICAL_ENDPOINTS.map(endpoint => ({
  ...endpoint,
  exists: routeExists(endpoint.method, endpoint.path, backendRoutes),
}))

const missingFrontend = []

for (const [key, endpoint] of frontendEndpoints.entries()) {
  if (endpoint.path.includes('${')) {
    continue
  }

  if (endpoint.path.startsWith('settings/') || endpoint.path.startsWith('google-doc-pages/')) {
    if (!routeExists(endpoint.method, endpoint.path, backendRoutes)) {
      missingFrontend.push({
        key,
        files: [...endpoint.files].sort(),
      })
    }
  }
}

console.log('Endpoint contract report')
console.log('=======================')
for (const row of coverageRows) {
  console.log(`${row.exists ? 'OK  ' : 'MISS'} ${row.method} ${row.path}`)
}

if (missingFrontend.length > 0) {
  console.log('\nFrontend calls without backend route:')
  for (const item of missingFrontend) {
    console.log(`- ${item.key}`)
    for (const file of item.files) {
      console.log(`  - ${file}`)
    }
  }
}

if (criticalFailures.length > 0) {
  console.error('\nCritical endpoint contract failed.')
  process.exit(1)
}

console.log('\nCritical endpoint contract passed.')
