# Rollback Plan - Modern Admin Styler V2 (v2.2.0)

## Overview

This document provides detailed procedures for rolling back the Modern Admin Styler V2 plugin from version 2.2.0 (REST API migration) to the previous stable version in case of critical issues.

---

## Rollback Decision Matrix

### When to Execute Rollback

| Severity | Issue Type | Action | Timeframe |
|----------|-----------|--------|-----------|
| **Critical** | Data loss/corruption | Immediate rollback | < 5 minutes |
| **Critical** | Security vulnerability | Immediate rollback | < 5 minutes |
| **Critical** | Complete functionality failure | Immediate rollback | < 10 minutes |
| **High** | Major feature broken | Rollback within 1 hour | < 30 minutes |
| **High** | Performance degradation > 50% | Rollback within 2 hours | < 30 minutes |
| **Medium** | Minor feature issues | Evaluate, may not rollback | N/A |
| **Low** | Cosmetic issues | Do not rollback | N/A |

### Rollback Authority

**Authorized to initiate rollback:**
- Lead Developer
- DevOps Lead
- CTO/Technical Director
- On-call Engineer (for critical issues)

**Notification required:**
- Product Manager
- Support Team Lead
- All stakeholders

---

## Pre-Rollback Checklist

Before initiating rollback:

- [ ] Confirm issue severity warrants rollback
- [ ] Document the issue thoroughly
- [ ] Capture error logs and screenshots
- [ ] Notify stakeholders of impending rollback
- [ ] Verify backup integrity
- [ ] Prepare rollback communication

---

## Rollback Procedures

### Method 1: Quick Rollback (Recommended)

**Time Required**: 5-10 minutes
**Risk Level**: Low
**Use When**: Need immediate restoration

#### Step 1: Prepare Environment
```bash
# Navigate to plugins directory
cd /path/to/wordpress/wp-content/plugins/

# Verify backup exists
ls -lh backup-pre-v2.2.0.tar.gz
ls -lh backup-pre-v2.2.0.sql

# Create emergency backup of current state (just in case)
tar -czf emergency-backup-$(date +%Y%m%d-%H%M%S).tar.gz modern-admin-styler-v2/
```

#### Step 2: Deactivate Current Version
```bash
# Via WP-CLI
wp plugin deactivate modern-admin-styler-v2

# Or via WordPress Admin
# Navigate to Plugins → Deactivate Modern Admin Styler V2
```

#### Step 3: Restore Plugin Files
```bash
# Remove current version
rm -rf modern-admin-styler-v2/

# Extract backup
tar -xzf backup-pre-v2.2.0.tar.gz

# Verify extraction
ls -la modern-admin-styler-v2/

# Set correct permissions
chmod -R 755 modern-admin-styler-v2/
chown -R www-data:www-data modern-admin-styler-v2/
```

#### Step 4: Restore Database (if needed)
```bash
# Check if database changes were made
wp db query "SHOW TABLES LIKE 'wp_mas_v2_%'"

# If tables were modified, restore database
wp db import backup-pre-v2.2.0.sql

# Verify database restoration
wp db check
```

#### Step 5: Restore Settings
```bash
# Restore plugin settings
wp option update mas_v2_settings "$(cat settings-backup.json)"

# Verify settings
wp option get mas_v2_settings
```

#### Step 6: Reactivate Plugin
```bash
# Activate previous version
wp plugin activate modern-admin-styler-v2

# Verify activation
wp plugin list | grep modern-admin-styler-v2
```

#### Step 7: Clear Caches
```bash
# Clear WordPress caches
wp cache flush

# Clear transients
wp transient delete --all

# Clear object cache (if using Redis/Memcached)
wp cache flush

# Clear opcache (if available)
wp eval 'opcache_reset();'
```

#### Step 8: Verify Rollback
```bash
# Check plugin version
wp plugin get modern-admin-styler-v2 --field=version

# Test basic functionality
wp eval 'do_action("admin_init");'

# Check for errors
tail -n 50 /path/to/wordpress/wp-content/debug.log
```

---

### Method 2: Manual Rollback via WordPress Admin

**Time Required**: 10-15 minutes
**Risk Level**: Low
**Use When**: WP-CLI not available

#### Step 1: Access WordPress Admin
1. Log in to WordPress admin panel
2. Navigate to Plugins → Installed Plugins

#### Step 2: Deactivate Plugin
1. Find "Modern Admin Styler V2"
2. Click "Deactivate"
3. Wait for confirmation

#### Step 3: Delete Current Version
1. Click "Delete" under Modern Admin Styler V2
2. Confirm deletion
3. Wait for completion

#### Step 4: Upload Previous Version
1. Click "Add New" → "Upload Plugin"
2. Choose backup ZIP file (previous version)
3. Click "Install Now"
4. Wait for upload and installation

#### Step 5: Activate Previous Version
1. Click "Activate Plugin"
2. Verify activation success

#### Step 6: Restore Settings (via phpMyAdmin)
1. Access phpMyAdmin
2. Select WordPress database
3. Find `wp_options` table
4. Locate `mas_v2_settings` row
5. Update `option_value` with backup data
6. Save changes

#### Step 7: Clear Caches
1. Navigate to Settings → Permalinks
2. Click "Save Changes" (flushes rewrite rules)
3. Clear browser cache
4. Clear any caching plugins

---

### Method 3: Database-Only Rollback

**Time Required**: 2-3 minutes
**Risk Level**: Very Low
**Use When**: Only database changes need reverting

```bash
# Restore only the settings
wp option update mas_v2_settings "$(cat settings-backup.json)"

# Restore custom tables (if any were added)
wp db query < backup-tables-only.sql

# Clear caches
wp cache flush
wp transient delete --all
```

---

## Post-Rollback Verification

### Immediate Checks (< 5 minutes)

- [ ] Plugin activated successfully
- [ ] No PHP errors in logs
- [ ] Admin interface loads
- [ ] Settings page accessible
- [ ] Basic functionality works

### Functional Tests (< 15 minutes)

```bash
# Test settings retrieval
wp option get mas_v2_settings

# Test admin page load
curl -I https://your-site.com/wp-admin/admin.php?page=modern-admin-styler-v2

# Check for JavaScript errors
# Open browser console and navigate to plugin settings page

# Test settings save
# Make a small change and save via admin interface
```

### User Verification (< 30 minutes)

- [ ] Test with admin user account
- [ ] Test with editor user account
- [ ] Verify no data loss
- [ ] Check all features functional
- [ ] Confirm performance acceptable

---

## Communication Plan

### During Rollback

**Immediate Notification** (within 5 minutes):
```
Subject: URGENT: Modern Admin Styler V2 Rollback in Progress

We are currently rolling back Modern Admin Styler V2 from version 2.2.0 
to the previous stable version due to [ISSUE DESCRIPTION].

Status: In Progress
ETA: [TIME]
Impact: [DESCRIPTION]

Updates will be provided every 15 minutes.
```

### After Rollback

**Success Notification**:
```
Subject: Modern Admin Styler V2 Rollback Complete

The rollback of Modern Admin Styler V2 has been completed successfully.

Previous Version: 2.2.0
Current Version: [PREVIOUS VERSION]
Rollback Time: [DURATION]
Reason: [ISSUE DESCRIPTION]

All functionality has been verified and is working normally.

Next Steps:
- Issue analysis and root cause investigation
- Fix development and testing
- New deployment plan
```

### Stakeholder Communication

**Who to Notify:**
1. Product Manager
2. Support Team
3. Development Team
4. QA Team
5. End Users (if customer-facing)

**Communication Channels:**
- Email
- Slack/Teams
- Status page
- Support ticket system

---

## Root Cause Analysis

After successful rollback, conduct RCA:

### 1. Issue Documentation
- What went wrong?
- When was it detected?
- What was the impact?
- How many users affected?

### 2. Timeline
- Deployment time
- Issue detection time
- Rollback initiation time
- Rollback completion time
- Total downtime

### 3. Root Cause
- Technical cause
- Process failure
- Testing gap
- Communication issue

### 4. Prevention
- What could have prevented this?
- What tests were missing?
- What processes need improvement?

### 5. Action Items
- Immediate fixes needed
- Long-term improvements
- Process changes
- Training requirements

---

## Rollback Testing

### Pre-Deployment Rollback Test

Before deploying v2.2.0, test the rollback procedure:

```bash
# 1. Deploy v2.2.0 to staging
wp plugin update modern-admin-styler-v2 --version=2.2.0

# 2. Make some changes
wp option update mas_v2_settings '{"test":"rollback"}'

# 3. Execute rollback procedure
# Follow Method 1 steps above

# 4. Verify rollback success
wp plugin get modern-admin-styler-v2 --field=version
wp option get mas_v2_settings

# 5. Document any issues
```

---

## Emergency Contacts

### Primary Contacts
- **Lead Developer**: [Name] - [Phone] - [Email]
- **DevOps Lead**: [Name] - [Phone] - [Email]
- **On-Call Engineer**: [Phone] - [Pager]

### Secondary Contacts
- **CTO**: [Name] - [Phone] - [Email]
- **Product Manager**: [Name] - [Phone] - [Email]
- **Support Lead**: [Name] - [Phone] - [Email]

### External Support
- **Hosting Provider**: [Support Number]
- **Database Admin**: [Contact Info]
- **Security Team**: [Contact Info]

---

## Rollback Scenarios & Solutions

### Scenario 1: REST API Endpoints Not Working
**Symptoms**: 404 errors on all REST endpoints
**Rollback Required**: No
**Solution**: Flush rewrite rules
```bash
wp rewrite flush
```

### Scenario 2: Settings Not Saving
**Symptoms**: Changes don't persist
**Rollback Required**: Maybe
**Solution**: Check database, restore settings only
```bash
wp option update mas_v2_settings "$(cat settings-backup.json)"
```

### Scenario 3: Complete Plugin Failure
**Symptoms**: White screen, fatal errors
**Rollback Required**: Yes (Immediate)
**Solution**: Execute Method 1 (Quick Rollback)

### Scenario 4: Performance Degradation
**Symptoms**: Slow admin interface
**Rollback Required**: Evaluate
**Solution**: Try optimization first, rollback if > 50% slower
```bash
wp cache flush
wp transient delete --all
```

### Scenario 5: Data Corruption
**Symptoms**: Settings lost or corrupted
**Rollback Required**: Yes (Immediate)
**Solution**: Execute full rollback with database restore

---

## Rollback Metrics

Track these metrics for each rollback:

- **Detection Time**: Time from deployment to issue detection
- **Decision Time**: Time from detection to rollback decision
- **Execution Time**: Time to complete rollback
- **Verification Time**: Time to verify rollback success
- **Total Downtime**: Total time of impaired functionality
- **Users Affected**: Number of users impacted
- **Data Loss**: Any data lost (should be zero)

---

## Lessons Learned Template

After each rollback, document:

```markdown
## Rollback Incident Report

**Date**: [DATE]
**Version**: 2.2.0
**Rollback Duration**: [DURATION]

### What Happened
[Description of the issue]

### Why It Happened
[Root cause analysis]

### Impact
- Users affected: [NUMBER]
- Downtime: [DURATION]
- Data loss: [YES/NO]

### What Went Well
- [Item 1]
- [Item 2]

### What Could Be Improved
- [Item 1]
- [Item 2]

### Action Items
- [ ] [Action 1] - Owner: [NAME] - Due: [DATE]
- [ ] [Action 2] - Owner: [NAME] - Due: [DATE]

### Prevention
[How to prevent this in the future]
```

---

## Appendix

### A. Backup Verification Script

```bash
#!/bin/bash
# verify-backup.sh

echo "Verifying backup integrity..."

# Check file backup
if [ -f "backup-pre-v2.2.0.tar.gz" ]; then
    echo "✓ File backup exists"
    tar -tzf backup-pre-v2.2.0.tar.gz > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✓ File backup is valid"
    else
        echo "✗ File backup is corrupted"
        exit 1
    fi
else
    echo "✗ File backup not found"
    exit 1
fi

# Check database backup
if [ -f "backup-pre-v2.2.0.sql" ]; then
    echo "✓ Database backup exists"
    head -n 1 backup-pre-v2.2.0.sql | grep -q "MySQL dump"
    if [ $? -eq 0 ]; then
        echo "✓ Database backup is valid"
    else
        echo "✗ Database backup may be corrupted"
        exit 1
    fi
else
    echo "✗ Database backup not found"
    exit 1
fi

echo "✓ All backups verified successfully"
```

### B. Quick Rollback Script

```bash
#!/bin/bash
# quick-rollback.sh

set -e

echo "Starting quick rollback..."

# Deactivate plugin
wp plugin deactivate modern-admin-styler-v2

# Backup current state
tar -czf emergency-backup-$(date +%Y%m%d-%H%M%S).tar.gz modern-admin-styler-v2/

# Remove current version
rm -rf modern-admin-styler-v2/

# Restore previous version
tar -xzf backup-pre-v2.2.0.tar.gz

# Set permissions
chmod -R 755 modern-admin-styler-v2/
chown -R www-data:www-data modern-admin-styler-v2/

# Restore settings
wp option update mas_v2_settings "$(cat settings-backup.json)"

# Activate plugin
wp plugin activate modern-admin-styler-v2

# Clear caches
wp cache flush
wp transient delete --all

echo "✓ Rollback complete"
```

---

**Document Version**: 1.0
**Last Updated**: 2025-06-10
**Approved By**: [Name]
**Next Review**: Before next major deployment
