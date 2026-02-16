#!/bin/bash

echo "📱 SMS OTP Setup for LearnIELTS"
echo "================================"
echo ""

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found!"
    echo "Please copy .env.example to .env first"
    exit 1
fi

echo "Choose your SMS provider:"
echo "1) MSG91 (Recommended for India)"
echo "2) Twilio (International)"
echo "3) Fast2SMS (Budget option)"
echo "4) Cache (Development only - no real SMS)"
echo ""
read -p "Enter choice (1-4): " choice

case $choice in
    1)
        echo ""
        echo "📱 Setting up MSG91..."
        echo ""
        echo "Please provide your MSG91 credentials:"
        echo "(Get them from: https://msg91.com/in/signup)"
        echo ""
        read -p "Auth Key: " auth_key
        read -p "Sender ID (default: LRNIEL): " sender_id
        sender_id=${sender_id:-LRNIEL}
        read -p "Template ID (optional for testing): " template_id
        read -p "Route (1=promotional, 4=transactional, default: 4): " route
        route=${route:-4}
        
        # Update .env
        if grep -q "SMS_PROVIDER=" .env; then
            sed -i "s/SMS_PROVIDER=.*/SMS_PROVIDER=msg91/" .env
        else
            echo "SMS_PROVIDER=msg91" >> .env
        fi
        
        if grep -q "MSG91_AUTH_KEY=" .env; then
            sed -i "s/MSG91_AUTH_KEY=.*/MSG91_AUTH_KEY=$auth_key/" .env
        else
            echo "MSG91_AUTH_KEY=$auth_key" >> .env
        fi
        
        if grep -q "MSG91_SENDER_ID=" .env; then
            sed -i "s/MSG91_SENDER_ID=.*/MSG91_SENDER_ID=$sender_id/" .env
        else
            echo "MSG91_SENDER_ID=$sender_id" >> .env
        fi
        
        if [ ! -z "$template_id" ]; then
            if grep -q "MSG91_TEMPLATE_ID=" .env; then
                sed -i "s/MSG91_TEMPLATE_ID=.*/MSG91_TEMPLATE_ID=$template_id/" .env
            else
                echo "MSG91_TEMPLATE_ID=$template_id" >> .env
            fi
        fi
        
        if grep -q "MSG91_ROUTE=" .env; then
            sed -i "s/MSG91_ROUTE=.*/MSG91_ROUTE=$route/" .env
        else
            echo "MSG91_ROUTE=$route" >> .env
        fi
        
        echo ""
        echo "✅ MSG91 configured successfully!"
        ;;
        
    2)
        echo ""
        echo "📱 Setting up Twilio..."
        echo ""
        echo "Please provide your Twilio credentials:"
        echo "(Get them from: https://www.twilio.com/console)"
        echo ""
        read -p "Account SID: " sid
        read -p "Auth Token: " token
        read -p "From Number (with country code, e.g., +1234567890): " from_number
        
        # Update .env
        if grep -q "SMS_PROVIDER=" .env; then
            sed -i "s/SMS_PROVIDER=.*/SMS_PROVIDER=twilio/" .env
        else
            echo "SMS_PROVIDER=twilio" >> .env
        fi
        
        if grep -q "TWILIO_SID=" .env; then
            sed -i "s/TWILIO_SID=.*/TWILIO_SID=$sid/" .env
        else
            echo "TWILIO_SID=$sid" >> .env
        fi
        
        if grep -q "TWILIO_AUTH_TOKEN=" .env; then
            sed -i "s/TWILIO_AUTH_TOKEN=.*/TWILIO_AUTH_TOKEN=$token/" .env
        else
            echo "TWILIO_AUTH_TOKEN=$token" >> .env
        fi
        
        if grep -q "TWILIO_FROM_NUMBER=" .env; then
            sed -i "s/TWILIO_FROM_NUMBER=.*/TWILIO_FROM_NUMBER=$from_number/" .env
        else
            echo "TWILIO_FROM_NUMBER=$from_number" >> .env
        fi
        
        echo ""
        echo "✅ Twilio configured successfully!"
        ;;
        
    3)
        echo ""
        echo "📱 Setting up Fast2SMS..."
        echo ""
        echo "Please provide your Fast2SMS API key:"
        echo "(Get it from: https://www.fast2sms.com/dashboard/dev-api)"
        echo ""
        read -p "API Key: " api_key
        
        # Update .env
        if grep -q "SMS_PROVIDER=" .env; then
            sed -i "s/SMS_PROVIDER=.*/SMS_PROVIDER=fast2sms/" .env
        else
            echo "SMS_PROVIDER=fast2sms" >> .env
        fi
        
        if grep -q "FAST2SMS_API_KEY=" .env; then
            sed -i "s/FAST2SMS_API_KEY=.*/FAST2SMS_API_KEY=$api_key/" .env
        else
            echo "FAST2SMS_API_KEY=$api_key" >> .env
        fi
        
        echo ""
        echo "✅ Fast2SMS configured successfully!"
        ;;
        
    4)
        echo ""
        echo "📱 Setting up Cache provider (Development only)..."
        
        # Update .env
        if grep -q "SMS_PROVIDER=" .env; then
            sed -i "s/SMS_PROVIDER=.*/SMS_PROVIDER=cache/" .env
        else
            echo "SMS_PROVIDER=cache" >> .env
        fi
        
        echo ""
        echo "✅ Cache provider configured!"
        echo "⚠️  Note: This will NOT send real SMS messages"
        echo "⚠️  OTP will be returned in API response (debug mode only)"
        ;;
        
    *)
        echo "❌ Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "🎉 Setup complete!"
echo ""
echo "Next steps:"
echo "1. Test OTP sending:"
echo "   curl -X POST http://localhost:8000/api/auth/send-otp \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"mobile\": \"9550633604\"}'"
echo ""
echo "2. Check logs if there are issues:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "3. For production, make sure to:"
echo "   - Set APP_DEBUG=false"
echo "   - Complete DLT registration (for India)"
echo "   - Monitor SMS delivery rates"
echo ""
