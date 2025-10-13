# AINSTEIN - Git Workflow Quick Reference

**Version**: 1.0
**Last Update**: 2025-10-13
**For**: Development Team

---

## Branch Structure

```
┌─────────────────────────────────────────────────────┐
│                    production                        │  ← Live server
│                    (protected)                       │
└──────────────────────┬──────────────────────────────┘
                       │ merge from master (releases)
                       │
┌──────────────────────▼──────────────────────────────┐
│                     master                           │  ← Source of truth
│                   (protected)                        │
└──────────────────────┬──────────────────────────────┘
                       │ merge via PR
                       │
┌──────────────────────▼──────────────────────────────┐
│                    develop                           │  ← Integration
│                                                      │
└─┬────────┬─────────┬──────────┬────────────────────┘
  │        │         │          │
  │        │         │          │
  ▼        ▼         ▼          ▼
feature/ feature/  bugfix/   hotfix/
crewai   campaign  analytics  critical-fix
```

---

## 🚀 Quick Commands

### Start New Feature

```bash
# 1. Update develop
git checkout develop
git pull origin develop

# 2. Create feature branch
git checkout -b feature/nome-feature

# 3. Work and commit
git add .
git commit -m "feat(scope): description"

# 4. Push and create PR
git push -u origin feature/nome-feature
# Open PR on GitHub: feature/nome → develop
```

---

### Fix Bug (Non-Critical)

```bash
# 1. Create bugfix branch from develop
git checkout develop
git pull origin develop
git checkout -b bugfix/nome-bug

# 2. Fix and commit
git add .
git commit -m "fix(scope): bug description"

# 3. Push and PR
git push -u origin bugfix/nome-bug
# Open PR: bugfix/nome → develop
```

---

### Critical Hotfix

```bash
# 1. Create hotfix from master
git checkout master
git pull origin master
git checkout -b hotfix/critical-fix-$(date +%Y-%m-%d)

# 2. Fix and commit
git add .
git commit -m "fix!: critical bug description"

# 3. Merge to master
git checkout master
git merge hotfix/critical-fix-YYYY-MM-DD --no-ff

# 4. Tag and push
git tag -a v1.0.x -m "Hotfix: description"
git push origin master --tags

# 5. Merge to develop
git checkout develop
git merge hotfix/critical-fix-YYYY-MM-DD --no-ff
git push origin develop

# 6. Delete hotfix branch
git branch -d hotfix/critical-fix-YYYY-MM-DD
git push origin --delete hotfix/critical-fix-YYYY-MM-DD
```

---

### Release to Production

```bash
# 1. Create release branch
git checkout develop
git checkout -b release/v1.x.0

# 2. Final testing and bug fixes
# Only bug fixes allowed in release branch

# 3. Merge to master
git checkout master
git merge release/v1.x.0 --no-ff
git tag -a v1.x.0 -m "Release v1.x.0: description"
git push origin master --tags

# 4. Merge back to develop
git checkout develop
git merge release/v1.x.0 --no-ff
git push origin develop

# 5. Update production
git checkout production
git merge master --ff-only
git push origin production

# 6. Delete release branch
git branch -d release/v1.x.0
```

---

## 📋 Commit Message Format

### Structure

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

| Type | Usage | Example |
|------|-------|---------|
| `feat` | New feature | `feat(campaign): add PMAX support` |
| `fix` | Bug fix | `fix(analytics): resolve chart crash` |
| `docs` | Documentation | `docs(readme): update installation` |
| `style` | Formatting | `style(auth): format code` |
| `refactor` | Code change | `refactor(user): simplify logic` |
| `test` | Tests | `test(api): add endpoint tests` |
| `chore` | Maintenance | `chore(deps): update Laravel` |
| `perf` | Performance | `perf(query): optimize N+1` |

### Examples

```bash
# Simple feature
git commit -m "feat(crewai): add agent management UI"

# Bug fix
git commit -m "fix(subscriptions): resolve payment gateway timeout"

# With body
git commit -m "feat(campaign): add PMAX campaign generator

- Implement Google Ads API integration
- Add Italian language support
- Create UI components for settings

Closes #123"

# Breaking change
git commit -m "feat(auth)!: migrate to Sanctum v3

BREAKING CHANGE: Old token format no longer supported
Users need to re-authenticate after deployment"
```

---

## 🏷️ Version Tagging

### Semantic Versioning

```
v<MAJOR>.<MINOR>.<PATCH>

v1.2.3
│ │ │
│ │ └─ Patch: Bug fixes, hotfixes
│ └─── Minor: New features (backward compatible)
└───── Major: Breaking changes
```

### Examples

```bash
# Patch release (bug fix)
git tag -a v1.0.1 -m "Hotfix: analytics crash"

# Minor release (new feature)
git tag -a v1.1.0 -m "Feature: CrewAI integration"

# Major release (breaking changes)
git tag -a v2.0.0 -m "Major: Laravel 11 upgrade"

# Push tags
git push origin --tags
```

---

## 🛡️ Branch Protection Rules

### Master Branch

- ✅ Require pull request reviews (min 1)
- ✅ Require status checks to pass (CI/CD)
- ✅ Require branches to be up to date
- ✅ Require linear history
- ❌ Allow force pushes
- ❌ Allow deletions

### Develop Branch

- ✅ Require pull request reviews
- ✅ Require status checks to pass
- ⚠️ Allow fast-forward merges
- ❌ Allow force pushes
- ❌ Allow deletions

---

## 🔄 Common Workflows

### Sync Fork/Branch

```bash
# Add upstream (if not already)
git remote add upstream https://github.com/organization/ainstein.git

# Fetch and merge
git fetch upstream
git checkout develop
git merge upstream/develop
git push origin develop
```

---

### Resolve Merge Conflicts

```bash
# 1. Update your branch
git checkout feature/your-feature
git fetch origin
git merge origin/develop

# 2. Resolve conflicts in files
# Edit conflicted files manually

# 3. Mark as resolved
git add <resolved-files>
git commit -m "merge: resolve conflicts with develop"

# 4. Push
git push origin feature/your-feature
```

---

### Squash Commits Before Merge

```bash
# Interactive rebase (last 3 commits)
git rebase -i HEAD~3

# In editor, change "pick" to "squash" for commits to merge
# Save and exit

# Force push (only on feature branch)
git push origin feature/your-feature --force
```

---

### Undo Last Commit (Keep Changes)

```bash
# Soft reset
git reset --soft HEAD~1

# Changes are staged, fix and recommit
git commit -m "fix: corrected commit message"
```

---

### Cherry-Pick Specific Commit

```bash
# From another branch
git checkout target-branch
git cherry-pick <commit-hash>

# If conflicts
git status
# Resolve conflicts
git cherry-pick --continue
```

---

## 📊 Useful Git Commands

### Status and History

```bash
# Detailed status
git status -vv

# Graphical log
git log --all --graph --oneline --decorate -20

# Files changed in last commit
git diff --name-only HEAD~1

# Who changed what
git blame <file>

# Search commits
git log --grep="keyword"
```

---

### Branch Management

```bash
# List all branches (local + remote)
git branch -a

# List branches with last commit
git branch -vv

# Delete local branch
git branch -d feature/old-feature

# Delete remote branch
git push origin --delete feature/old-feature

# Rename branch
git branch -m old-name new-name
```

---

### Stash (Temporary Save)

```bash
# Save current work
git stash save "WIP: feature description"

# List stashes
git stash list

# Apply last stash
git stash pop

# Apply specific stash
git stash apply stash@{0}

# Clear all stashes
git stash clear
```

---

## 🚨 Emergency Commands

### Undo Force Push (Recovery)

```bash
# Find commit before force push
git reflog

# Reset to that commit
git reset --hard HEAD@{n}

# Force push again (if necessary)
git push origin branch-name --force
```

---

### Restore Deleted Branch

```bash
# Find branch commit in reflog
git reflog

# Recreate branch
git checkout -b recovered-branch <commit-hash>
```

---

### Abort Merge/Rebase

```bash
# Abort merge
git merge --abort

# Abort rebase
git rebase --abort

# Abort cherry-pick
git cherry-pick --abort
```

---

## 📞 Getting Help

### Git Resources

- **Official Git Docs**: https://git-scm.com/doc
- **GitHub Flow**: https://docs.github.com/en/get-started/quickstart/github-flow
- **Git Cheat Sheet**: https://education.github.com/git-cheat-sheet-education.pdf

### Team Contacts

- **Git Issues**: #git-help Slack channel
- **PR Reviews**: Tag @team-lead
- **Emergency**: Contact DevOps team

---

## 🎯 Best Practices

### DO ✅

- ✅ Commit often, push daily
- ✅ Write descriptive commit messages
- ✅ Test before pushing
- ✅ Keep branches up to date
- ✅ Delete branches after merge
- ✅ Use pull requests for all changes
- ✅ Review others' PRs

### DON'T ❌

- ❌ Force push to master/develop
- ❌ Commit directly to master
- ❌ Commit large binary files
- ❌ Commit sensitive data (.env)
- ❌ Create long-lived feature branches
- ❌ Merge without tests passing
- ❌ Push broken code

---

## 🔐 Security Checklist

Before committing:

- [ ] No API keys in code
- [ ] No passwords in code
- [ ] .env file not committed
- [ ] credentials.json not committed
- [ ] No hardcoded secrets

If accidentally committed secrets:

```bash
# Remove from history
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch path/to/secret-file" \
  --prune-empty --tag-name-filter cat -- --all

# Force push (with caution)
git push origin --force --all

# Rotate compromised secrets immediately!
```

---

## 📈 Git Aliases (Optional)

Add to `~/.gitconfig`:

```ini
[alias]
    st = status -sb
    co = checkout
    br = branch -vv
    ci = commit
    unstage = reset HEAD --
    last = log -1 HEAD
    visual = log --all --graph --decorate --oneline
    aliases = config --get-regexp alias
```

Usage:
```bash
git st       # Instead of git status
git co develop  # Instead of git checkout develop
git visual   # Pretty log
```

---

## ⚡ Quick Troubleshooting

### Problem: "Your branch is behind"

```bash
git pull origin branch-name
```

### Problem: "Diverged branches"

```bash
git pull --rebase origin branch-name
```

### Problem: "Merge conflict"

```bash
# 1. Find conflicted files
git status

# 2. Edit files, remove conflict markers
# <<<<<<< HEAD
# =======
# >>>>>>>

# 3. Mark as resolved
git add <file>
git commit -m "merge: resolve conflicts"
```

### Problem: "Detached HEAD"

```bash
git checkout -b temp-branch
git checkout original-branch
git merge temp-branch
```

---

**END OF QUICK REFERENCE**

*For detailed Git strategy, see: GIT_ANALYSIS_REPORT.md*
*Last Updated: 2025-10-13*
