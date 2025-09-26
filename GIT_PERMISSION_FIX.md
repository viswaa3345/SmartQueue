# Git Permission Error 403 - Solution Guide

## Problem
```
remote: {"auth_status":"access_denied_to_user","body":"Permission to viswaa3345/SmartQueue.git denied to subashshanmugmam."}
fatal: unable to access 'https://github.com/viswaa3345/SmartQueue.git/': The requested URL returned error: 403
```

## Current Situation
- **Repository**: `viswaa3345/SmartQueue.git`
- **Current User**: `subashshanmugmam` 
- **Issue**: No write permission to push
- **Pending Commit**: `d85d839 (HEAD -> main) Commited by Subash S`

## Solution Options

### ✅ Option 1: Get Collaborator Access (RECOMMENDED)
**Best if you're working together with viswaa3345**

1. Ask `viswaa3345` to add you as a collaborator:
   - Go to: https://github.com/viswaa3345/SmartQueue
   - Click **Settings** tab
   - Click **Collaborators** in sidebar
   - Click **Add people**
   - Add username: `subashshanmugmam`
   - Choose **Write** or **Admin** permission
   - Send invitation

2. Accept the email invitation when received

3. Then push your changes:
   ```bash
   git push origin main
   ```

### ✅ Option 2: Fork Repository (ALTERNATIVE)
**If you need your own copy of the project**

1. **Fork the repository**:
   - Go to: https://github.com/viswaa3345/SmartQueue
   - Click **Fork** button (top right)
   - This creates: `subashshanmugmam/SmartQueue`

2. **Update your local remote**:
   ```bash
   cd /opt/lampp/htdocs/Viswaa/SmartQueue
   ./update_remote.sh
   ```
   
   Or manually:
   ```bash
   git remote set-url origin https://github.com/subashshanmugmam/SmartQueue.git
   git push origin main
   ```

### ✅ Option 3: Switch GitHub Account
**If you have access to viswaa3345 account**

1. **Logout current account**:
   ```bash
   gh auth logout
   ```

2. **Login with viswaa3345**:
   ```bash
   gh auth login
   ```
   
3. **Follow prompts** to authenticate with viswaa3345 credentials

4. **Push changes**:
   ```bash
   git push origin main
   ```

## Verification Commands

After applying any solution, verify with:
```bash
# Check authentication
gh auth status

# Check remote URL
git remote -v

# Try pushing
git push origin main
```

## Current Commit to Push
- Commit ID: `d85d839`
- Message: "Commited by Subash S"
- This contains all the database connection fixes we made

Choose the option that best fits your collaboration setup with viswaa3345!