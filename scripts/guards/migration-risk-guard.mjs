#!/usr/bin/env node

import { readFileSync } from 'node:fs'
import { execSync } from 'node:child_process'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const scriptDir = dirname(fileURLToPath(import.meta.url))
const root = join(scriptDir, '..', '..')
const preferredBaseRef = process.env.GUARD_MIGRATION_BASE || 'origin/main_v8'
const baseCandidates = [preferredBaseRef, 'main_v8', 'origin/main', 'main']

const resolveBaseRef = () => {
  for (const candidate of baseCandidates) {
    try {
      execSync(`git -C "${root}" rev-parse --verify ${candidate}`, { stdio: 'ignore' })
      return candidate
    } catch {
      // try next
    }
  }

  throw new Error(`No valid base ref found. Tried: ${baseCandidates.join(', ')}`)
}

const baseRef = resolveBaseRef()

const changedFiles = execSync(`git -C "${root}" diff --name-only ${baseRef}...HEAD`, { encoding: 'utf8' })
  .split('\n')
  .map(line => line.trim())
  .filter(Boolean)
  .filter(file => file.startsWith('database/migrations/') && file.endsWith('.php'))

const riskyPatterns = [
  { label: 'drop table', regex: /Schema::drop(?:IfExists)?\s*\(/g },
  { label: 'drop column', regex: /->dropColumn\s*\(/g },
  { label: 'rename table', regex: /Schema::rename\s*\(/g },
  { label: 'rename column', regex: /->renameColumn\s*\(/g },
  { label: 'change column', regex: /->change\s*\(/g },
]

const allowLabel = process.env.GUARD_MIGRATION_ACK === '1'
const findings = []

for (const relativePath of changedFiles) {
  const absolutePath = join(root, relativePath)
  const content = readFileSync(absolutePath, 'utf8')

  for (const pattern of riskyPatterns) {
    if (pattern.regex.test(content)) {
      findings.push({ file: relativePath, risk: pattern.label })
    }
  }
}

if (findings.length === 0) {
  console.log(`No risky migration operations found against ${baseRef}.`)
  process.exit(0)
}

console.error(`Risky migration operations detected against ${baseRef}:`)
for (const finding of findings) {
  console.error(`- ${finding.file}: ${finding.risk}`)
}

if (!allowLabel) {
  console.error('\nSet GUARD_MIGRATION_ACK=1 to acknowledge intentional risky migrations.')
  process.exit(1)
}

console.log('\nAcknowledged with GUARD_MIGRATION_ACK=1.')
