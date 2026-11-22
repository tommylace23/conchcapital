# Deposit Confirmation Guide

## Overview
When users deposit crypto, transactions are created with "pending" status. You need to confirm them after verifying the funds have arrived in your Trezor wallet.

## Two Methods to Confirm Deposits

### Method 1: Manual Confirmation (Recommended for Now)

#### Step 1: Check Your Trezor Wallet
1. Connect your Trezor device
2. Open Trezor Suite
3. Check for incoming transactions matching the deposit amounts
4. Verify the transaction has 2+ confirmations on the blockchain

#### Step 2: Confirm via Admin API
Use the admin endpoint to confirm deposits:

**Get Pending Deposits:**
```
GET /api/admin/deposits/pending
```

**Confirm a Deposit:**
```
POST /api/admin/deposits/{transaction_id}/confirm
```

This will:
- Change transaction status from "pending" to "completed"
- Credit the user's account balance
- Log the admin action

### Method 2: Automatic Blockchain Checking (Optional)

#### Check Deposit Status via API:
```
POST /api/admin/deposits/{transaction_id}/check
```

This endpoint:
- Checks the blockchain for the transaction
- Returns confirmation count
- For Bitcoin: Uses BlockCypher API (free, no API key needed)
- For Ethereum/USDT: Requires API key setup (Etherscan)

**Response:**
```json
{
  "found": true,
  "confirmations": 3,
  "tx_hash": "abc123...",
  "confirmed": true,
  "message": "Transaction found with 3 confirmations"
}
```

## Workflow

### Current Recommended Workflow:

1. **User Initiates Deposit**
   - User enters amount and selects crypto
   - System generates Trezor address
   - Transaction created with "pending" status

2. **You Monitor Deposits**
   - Check Trezor Suite regularly
   - Or use blockchain explorer:
     - Bitcoin: https://blockchain.info/address/YOUR_BTC_ADDRESS
     - Ethereum: https://etherscan.io/address/YOUR_ETH_ADDRESS

3. **Match Transactions**
   - Compare amounts and timing
   - Verify 2+ confirmations

4. **Confirm Deposit**
   - Use admin panel or API endpoint
   - System credits user account automatically

## Admin Panel Integration (Future)

You can create an admin panel page to:
- View all pending deposits
- See user details, amounts, addresses
- Click "Confirm" button after verification
- See blockchain check results

## API Endpoints

### Get Pending Deposits
```http
GET /api/admin/deposits/pending
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "deposits": [
    {
      "id": 123,
      "user_id": 45,
      "amount": "100.00",
      "currency": "USD",
      "status": "pending",
      "metadata": {
        "crypto_currency": "BTC",
        "crypto_amount": 0.00222222,
        "crypto_address": "bc1qsrjg5th2xs4j7nwjhdr28wrn6803dnyty3j7t2"
      },
      "user": {
        "id": 45,
        "username": "user123",
        "email": "user@example.com"
      },
      "created_at": "2026-01-22T10:00:00Z"
    }
  ],
  "count": 1
}
```

### Confirm Deposit
```http
POST /api/admin/deposits/{id}/confirm
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "message": "Deposit confirmed successfully.",
  "transaction": {
    "id": 123,
    "status": "completed",
    "processed_at": "2026-01-22T10:30:00Z"
  },
  "user": {
    "id": 45,
    "real_balance": "100.00"
  }
}
```

### Check Deposit Status (Blockchain)
```http
POST /api/admin/deposits/{id}/check
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "found": true,
  "confirmations": 3,
  "tx_hash": "abc123...",
  "confirmed": true,
  "message": "Transaction found with 3 confirmations"
}
```

## Best Practices

1. **Regular Monitoring**: Check for deposits at least once daily
2. **Verify Amounts**: Always match the exact crypto amount
3. **Check Confirmations**: Wait for 2+ confirmations before crediting
4. **Log Everything**: All confirmations are logged in admin actions
5. **Security**: Only admins can confirm deposits

## Future Enhancements

1. **Webhook Integration**: Set up webhooks from blockchain services
2. **Automatic Monitoring**: Scheduled job to check pending deposits
3. **Email Notifications**: Get notified when deposits need confirmation
4. **Admin Dashboard**: Visual interface for managing deposits
5. **Multi-currency Support**: Full API integration for ETH/USDT

## Troubleshooting

### Transaction Not Found
- Check if user sent to correct address
- Verify amount matches exactly
- Check transaction timing

### Confirmations Not Reaching 2
- Bitcoin: Usually takes 10-20 minutes
- Ethereum: Usually takes 2-5 minutes
- Wait a bit longer and check again

### API Errors
- Ensure you're using admin credentials
- Check transaction ID is correct
- Verify transaction is still pending
