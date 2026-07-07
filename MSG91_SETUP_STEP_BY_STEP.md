# MSG91 Setup - Step by Step Guide

## Step 1: Sign Up for MSG91

1. ✅ Go to https://msg91.com/in/signup
2. ✅ Fill in your details:
   - Name
   - Email
   - Mobile Number
   - Company Name
3. ✅ Verify your email and mobile number
4. ✅ You'll get **100 FREE SMS credits** for testing!

## Step 2: Create Auth Key (You are here!)

Based on your screenshot, you're on the right page. Here's what to do:

### Fill in the form:

1. **Name**: Enter a descriptive name
   ```
   LearnIELTS Production
   ```
   or
   ```
   LearnIELTS Development
   ```

2. **Select Rule**: Choose the appropriate rule
   - For testing: Select "Default" or create a new rule
   - For production: Create a specific rule with rate limits

3. **Where are you integrating?**: Enter your application details
   ```
   LearnIELTS Mobile App - OTP Authentication
   ```

4. **IP Security**: 
   - For development: Turn OFF (or leave ON and add your server IP)
   - For production: Turn ON and whitelist your server IP
   
   Your server IP: `98.82.135.101` (from your .env)

5. **Whitelisted IPs**: If IP Security is ON, add:
   ```
   98.82.135.101
   ```

6. Click **"Create"** button

### After Creating Auth Key:

You'll see your Auth Key in the list. It looks like:
```
334567A9pL6HqXYZ123456
```

**IMPORTANT**: Copy this Auth Key immediately! You'll need it for your .env file.

## Step 3: Configure Your Backend

### Option A: Using the Setup Script (Recommended)

```bash
cd backend
chmod +x setup-sms.sh
./setup-sms.sh
```

Choose option 1 (MSG91) and paste your Auth Key when prompted.

### Option B: Manual Configuration

Edit `backend/.env` and add:

```env
# SMS Configuration
SMS_PROVIDER=msg91
MSG91_AUTH_KEY=your_auth_key_here_from_step2
MSG91_SENDER_ID=LRNIEL
MSG91_ROUTE=4
```

Replace `your_auth_key_here_from_step2` with the actual Auth Key you copied.

## Step 4: Test OTP Sending

### Test 1: Send OTP
```bash
curl -X POST http://localhost:8000/api/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"mobile": "9550633604"}'
```

Expected response:
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "provider": "msg91",
  "otp": "123456"  // Only in debug mode
}
```

### Test 2: Check Logs
```bash
tail -f backend/storage/logs/laravel.log
```

Look for:
```
[2024-02-16 20:38:00] local.INFO: OTP generated for 9550633604: 123456
[2024-02-16 20:38:01] local.INFO: MSG91 SMS sent successfully to 9550633604
```

### Test 3: Check MSG91 Dashboard

1. Go to MSG91 Dashboard
2. Click on "Reports" or "SMS Logs"
3. You should see your sent SMS with status "Delivered"

## Step 5: Create SMS Template (For Production)

For production in India, you need DLT (Distributed Ledger Technology) registration:

### 5.1: Register on DLT Portal

1. Go to https://www.vilpower.in (or your telecom operator's DLT portal)
2. Register your company
3. Get your **Entity ID**

### 5.2: Create Template in MSG91

1. In MSG91 Dashboard, go to **SMS > Templates**
2. Click **"Create Template"**
3. Fill in the form:

   **Template Name:**
   ```
   LearnIELTS OTP
   ```

   **Template Content:**
   ```
   Your OTP for LearnIELTS is {#var#}. Valid for 5 minutes. Do not share with anyone.
   ```

   **Entity ID:** (from DLT registration)
   ```
   Your entity ID here
   ```

   **Template Type:** Transactional

4. Submit for approval (takes 24-48 hours)

5. Once approved, you'll get a **Template ID** like:
   ```
   6543210987654321
   ```

6. Add to your `.env`:
   ```env
   MSG91_TEMPLATE_ID=6543210987654321
   ```

## Step 6: Production Deployment

### Update .env for Production:

```env
# Application
APP_ENV=production
APP_DEBUG=false

# SMS Configuration
SMS_PROVIDER=msg91
MSG91_AUTH_KEY=your_production_auth_key
MSG91_SENDER_ID=LRNIEL
MSG91_TEMPLATE_ID=your_approved_template_id
MSG91_ROUTE=4
```

### Important Production Settings:

1. **Set APP_DEBUG=false** - This will hide OTP in API responses
2. **Use Template ID** - Required for DLT compliance in India
3. **Monitor Credits** - Check MSG91 dashboard regularly
4. **Set up Billing Alerts** - Get notified when credits are low

## Troubleshooting

### Issue 1: "Failed to send OTP"

**Check:**
1. Auth Key is correct in .env
2. Server IP is whitelisted (if IP Security is ON)
3. You have SMS credits remaining
4. Laravel logs for detailed error

**Solution:**
```bash
# Check logs
tail -f backend/storage/logs/laravel.log

# Test Auth Key
curl -X POST "https://api.msg91.com/api/v5/flow/" \
  -H "authkey: YOUR_AUTH_KEY" \
  -H "content-type: application/json" \
  -d '{
    "sender": "LRNIEL",
    "mobiles": "919550633604",
    "var1": "123456"
  }'
```

### Issue 2: SMS not received

**Check:**
1. Phone number format is correct (10 digits, no +91)
2. MSG91 dashboard shows "Delivered" status
3. Check spam/blocked messages on phone
4. Try with a different phone number

**Solution:**
- Check MSG91 Reports section for delivery status
- Verify phone number is not in DND (Do Not Disturb) list

### Issue 3: "Template not found" error

**Check:**
1. Template is approved in MSG91
2. Template ID is correct in .env
3. Using correct route (4 for transactional)

**Solution for Testing:**
```env
# Use route 1 for testing without template
MSG91_ROUTE=1
# Remove or comment out template ID
# MSG91_TEMPLATE_ID=
```

### Issue 4: IP Security blocking requests

**Check:**
1. Your server IP is whitelisted
2. IP Security is OFF for development

**Solution:**
- Add your server IP: `98.82.135.101`
- Or turn OFF IP Security for testing

## Cost Estimation

### MSG91 Pricing (India):
- **Transactional SMS**: ₹0.15 - ₹0.25 per SMS
- **Promotional SMS**: ₹0.10 - ₹0.15 per SMS

### Monthly Cost Example:
- 1000 users/month
- 2 OTPs per user (registration + login)
- Total: 2000 SMS/month
- **Cost: ₹300-500/month (~$4-6)**

### Free Credits:
- New accounts get **100 FREE SMS**
- Perfect for testing!

## Quick Reference

### Environment Variables:
```env
SMS_PROVIDER=msg91
MSG91_AUTH_KEY=your_auth_key
MSG91_SENDER_ID=LRNIEL
MSG91_TEMPLATE_ID=your_template_id  # Optional for testing
MSG91_ROUTE=4  # 1=promotional, 4=transactional
```

### API Endpoints:
- Send OTP: `POST /api/auth/send-otp`
- Verify OTP: `POST /api/auth/verify-otp`

### Useful Links:
- MSG91 Dashboard: https://control.msg91.com
- MSG91 API Docs: https://docs.msg91.com
- DLT Registration: https://www.vilpower.in
- Support: support@msg91.com

## Next Steps

1. ✅ Create Auth Key (you're doing this now!)
2. ⏳ Configure backend .env
3. ⏳ Test OTP sending
4. ⏳ Create DLT template (for production)
5. ⏳ Deploy to production

## Support

If you face any issues:
1. Check Laravel logs: `tail -f backend/storage/logs/laravel.log`
2. Check MSG91 dashboard for delivery status
3. Contact MSG91 support: support@msg91.com
4. Check this guide: `backend/OTP_SMS_SETUP_GUIDE.md`
