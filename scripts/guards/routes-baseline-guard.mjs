#!/usr/bin/env node

import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const scriptDir = dirname(fileURLToPath(import.meta.url))
const root = join(scriptDir, '..', '..')
const routeFile = join(root, 'routes/api.base.php')
const baselineFile = join(scriptDir, 'baselines/fork-routes.json')

const normalizePath = path => path.replace(/^api\//, '').replace(/^\/+|\/+$/g, '')
const joinPrefix = (a, b) => [a, b].filter(Boolean).map(s => s.replace(/^\/+|\/+$/g, '')).join('/')

const parseRoutes = content => {
  const lines = content.split('\n')
  const methods = ['get', 'post', 'put', 'patch', 'delete']
  const routes = new Set()
  const groupStack = []
  const pendingPrefixes = []
  let depth = 0

  const currentPrefix = () => groupStack.map(group => group.prefix).filter(Boolean).join('/')

  for (const line of lines) {
    const trimmed = line.trim()
    const openCount = (line.match(/\{/g) || []).length
    const closeCount = (line.match(/\}/g) || []).length

    for (const match of trimmed.matchAll(/->prefix\('([^']+)'\)/g)) {
      pendingPrefixes.push(normalizePath(match[1]))
    }

    const groupPrefixMatch = trimmed.match(/Route::group\(\['prefix'\s*=>\s*'([^']+)'/)
    if (groupPrefixMatch) {
      pendingPrefixes.push(normalizePath(groupPrefixMatch[1]))
    }

    const beginsGroup = trimmed.includes('->group(') || trimmed.includes('Route::group(')
    if (beginsGroup && openCount > 0) {
      const prefix = pendingPrefixes.length ? pendingPrefixes.join('/') : ''
      groupStack.push({ prefix, enterDepth: depth + openCount })
      pendingPrefixes.length = 0
    }

    for (const method of methods) {
      const match = trimmed.match(new RegExp(`Route::${method}\\s*\\(\\s*'([^']+)'`))
      if (match) {
        const fullPath = joinPrefix(currentPrefix(), normalizePath(match[1]))
        routes.add(`${method.toUpperCase()} ${fullPath}`)
      }
    }

    depth += openCount - closeCount
    while (groupStack.length && depth < groupStack[groupStack.length - 1].enterDepth) {
      groupStack.pop()
    }
  }

  return routes
}

const baseline = JSON.parse(readFileSync(baselineFile, 'utf8'))
const baselineRoutes = new Set(baseline.routes)
const currentRoutes = parseRoutes(readFileSync(routeFile, 'utf8'))

const missing = [...baselineRoutes].filter(route => !currentRoutes.has(route)).sort()
const extra = [...currentRoutes].filter(route => !baselineRoutes.has(route)).sort()
const allowRemoval = process.env.JUSTIFIED_ROUTE_REMOVAL === '1'

console.log(`Fork routes baseline: ${baselineRoutes.size}`)
console.log(`Current routes: ${currentRoutes.size}`)

if (missing.length) {
  console.error('\nMissing routes from fork baseline:')
  for (const route of missing) {
    console.error(`- ${route}`)
  }
}

if (extra.length) {
  console.log('\nNew routes not in baseline:')
  for (const route of extra) {
    console.log(`+ ${route}`)
  }
}

if (missing.length && !allowRemoval) {
  console.error('\nRoute baseline guard failed. Set JUSTIFIED_ROUTE_REMOVAL=1 only for intentional removals.')
  process.exit(1)
}

if (missing.length && allowRemoval) {
  console.log('\nRoute removals explicitly acknowledged with JUSTIFIED_ROUTE_REMOVAL=1.')
}

console.log('\nFork route baseline guard passed.')
