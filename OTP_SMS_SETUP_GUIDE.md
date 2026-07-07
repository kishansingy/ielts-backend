# OTP SMS Setup Guide for Production

## Current Issue
Your app is using a cache-based OTP system that doesn't actually send SMS messages. This works for development but fails in production.

## Solution Options

You need to integrate with an SMS gateway service. Here are the best options for India:

### Option 1: Twilio (Recommended - Most Reliable)
- **Cost**: ~$0.0079 per SMS in India
- **Reliability**: Excellent
- **Setup Time**: 15 minutes
- **Website**: https://www.twilio.com

### Option 2: MSG91 (Popular in India)
- **Cost**: ~₹0.15-0.25 per SMS
- **Reliability**: Good
- **Setup Time**: 10 minutes
- **Website**: https://msg91.com

### Option 3: Fast2SMS (Budget Option)
- **Cost**: ~₹0.10-0.20 per SMS
- **Reliability**: Good
- **Setup Time**: 10 minutes
- **Website**: https://www.fast2sms.com

### Option 4: AWS SNS (If using AWS)
- **Cost**: $0.00645 per SMS in India
- **Reliability**: Excellent
- **Setup Time**: 20 minutes
- **Website**: https://aws.amazon.com/sns/

## Implementation Guide

I'll show you how to implement **MSG91** (most popular in India):

### Step 1: Sign Up for MSG91

1. Go to https://msg91.com
2. Sign up for an account
3. Verify your email and mobile
4. Get free credits for testing

### Step 2: Get API Credentials

1. Login to MSG91 dashboard
2. Go to "API" section
3. Copy your **Auth Key**
4. Create an SMS template (required for DLT compliance)

### Step 3: Create DLT Template

For India, you need DLT (Distributed Ledger Technology) registration:

**Template Example:**
```
Your OTP for LearnIELTS is {#var#}. Valid for 5 minutes. Do not share with anyone.
```

**Template ID**: You'll get this after approval (usually 24-48 hours)

### Step 4: Update .env File

Add these to your `backend/.env`:

```env
# SMS Configuration
SMS_PROVIDER=msg91
MSG91_AUTH_KEY=your_auth_key_here
MSG91_SENDER_ID=LRNIEL
MSG91_TEMPLATE_ID=your_template_id_here
MSG91_ROUTE=4

# Alternative: Twilio
# SMS_PROVIDER=twilio
# TWILIO_SID=your_account_sid
# TWILIO_AUTH_TOKEN=your_auth_token
# TWILIO_FROM_NUMBER=+1234567890

# Alternative: Fast2SMS
# SMS_PROVIDER=fast2sms
# FAST2SMS_API_KEY=your_api_key_here
```

### Step 5: Install Required Package

```bash
cd backend
composer require guzzlehttp/guzzle
```

### Step 6: Use the New SMS Service

The SMS service has been created at `backend/app/Services/SmsService.php`

### Step 7: Test OTP

```bash
# Test sending OTP
curl -X POST http://localhost:8000/api/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"mobile": "9550633604"}'
```

## Quick Setup for MSG91

### 1. Get Free Credits
- Sign up at https://msg91.com
- Get 100 free SMS credits for testing

### 2. Get Auth Key
```
Dashboard > API > Auth Key
```

### 3. Create Template (for production)
```
Dashboard > SMS > Templates > Create Template

Template:
Your OTP for LearnIELTS is {#var#}. Valid for 5 minutes. Do not share.

Entity ID: (from DLT registration)
Template ID: (you'll get this after approval)
```

### 4. Update .env
```env
SMS_PROVIDER=msg91
MSG91_AUTH_KEY=your_auth_key_from_dashboard
MSG91_SENDER_ID=LRNIEL
MSG91_TEMPLATE_ID=your_template_id_after_approval
MSG91_ROUTE=4
```

### 5. For Testing (Without DLT)
You can test without DLT approval using route 1:
```env
MSG91_ROUTE=1
```

## Alternative: Twilio Setup (International)

### 1. Sign Up
- Go to https://www.twilio.com/try-twilio
- Get $15 free credit

### 2. Get Credentials
```
Console > Account Info
- Account SID
- Auth Token
```

### 3. Get Phone Number
```
Console > Phone Numbers > Buy a Number
(Choose a number with SMS capability)
```

### 4. Update .env
```env
SMS_PROVIDER=twilio
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+1234567890
```

## Testing in Development

For development/testing without spending money, you can use the cache-based system:

```env
SMS_PROVIDER=cache
APP_DEBUG=true
```

This will:
- Generate OTP and store in cache
- Return OTP in API response (only in debug mode)
- Not send actual SMS

## Production Checklist

- [ ] Choose SMS provider (MSG91 recommended for India)
- [ ] Sign up and get API credentials
- [ ] Create DLT template (for India)
- [ ] Update .env with credentials
- [ ] Test OTP sending
- [ ] Set APP_DEBUG=false in production
- [ ] Monitor SMS delivery rates
- [ ] Set up billing alerts

## Cost Estimation

For 1000 users per month:
- Average 2 OTPs per user (registration + login)
- Total: 2000 SMS/month
- Cost with MSG91: ₹300-500/month (~$4-6)
- Cost with Twilio: $16/month

## Troubleshooting

### OTP not received
1. Check SMS provider dashboard for delivery status
2. Verify phone number format (+91 prefix for India)
3. Check DLT template approval status
4. Verify API credentials in .env

### "Failed to send OTP" error
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Verify SMS provider API is accessible
3. Check account balance/credits
4. Verify .env configuration

### OTP expired
- Default expiry: 5 minutes
- Increase in SmsService.php if needed

## Support

For issues:
1. Check Laravel logs: `backend/storage/logs/laravel.log`
2. Check SMS provider dashboard
3. Test with cache provider first
4. Contact SMS provider support

## Security Best Practices

1. **Rate Limiting**: Limit OTP requests per phone number
2. **Expiry**: Keep OTP validity short (5 minutes)
3. **Attempts**: Limit verification attempts (3-5 max)
4. **Logging**: Log all OTP activities
5. **Monitoring**: Monitor unusual patterns
