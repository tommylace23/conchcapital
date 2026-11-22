# Trezor Setup Guide for Deposits

## Overview
This system uses Trezor hardware wallet addresses for receiving crypto deposits. The addresses are hardcoded and secured by your Trezor device.

## How It Works

1. **Deposit Address**: Users send crypto to your Trezor-secured address
2. **Funds Storage**: All deposits go to your Trezor wallet
3. **Access Control**: Only you (with physical Trezor device) can access these funds
4. **User Credits**: You manually credit user accounts after verifying deposits

## Setting Up Your Trezor Addresses

### Step 1: Connect Your Trezor Device
1. Connect your Trezor hardware wallet to your computer
2. Enter your PIN on the Trezor device
3. Open Trezor Suite (desktop or web)

### Step 2: Get Your Receiving Addresses

#### For Bitcoin (BTC):
1. In Trezor Suite, select "Bitcoin" account
2. Click on "Receive" tab
3. Copy the receiving address (starts with `bc1q` or `1` or `3`)
4. This is your `TREZOR_BTC_ADDRESS`

#### For Ethereum (ETH):
1. In Trezor Suite, select "Ethereum" account
2. Click on "Receive" tab
3. Copy the receiving address (starts with `0x`)
4. This is your `TREZOR_ETH_ADDRESS`

#### For USDT (Tether):
1. In Trezor Suite, select "Ethereum" account (USDT uses Ethereum network)
2. Click on "Receive" tab
3. Copy the receiving address (same as ETH address, starts with `0x`)
4. This is your `TREZOR_USDT_ADDRESS`

### Step 3: Add Addresses to Environment File

Add these to your `.env` file on the server:

```env
TREZOR_BTC_ADDRESS=bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh
TREZOR_ETH_ADDRESS=0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb
TREZOR_USDT_ADDRESS=0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb
```

**Replace with your actual Trezor addresses!**

## Important Security Notes

### ✅ Advantages:
- **Maximum Security**: Private keys never leave your Trezor device
- **Cold Storage**: Funds are stored offline
- **Physical Security**: Requires physical access to Trezor + PIN

### ⚠️ Considerations:
- **Manual Processing**: You need to manually verify and credit deposits
- **Physical Access Required**: You need the Trezor device to access funds
- **Not Automated**: No automatic deposit detection (requires manual monitoring)

## Deposit Processing Workflow

1. **User Initiates Deposit**:
   - User enters USD amount and selects crypto
   - System shows your Trezor address and QR code
   - User sends crypto to that address

2. **You Monitor Deposits**:
   - Check your Trezor wallet for incoming transactions
   - Match transactions to user deposits (by amount/timing)
   - Verify transaction confirmations (2+ confirmations recommended)

3. **Credit User Account**:
   - Use admin panel to credit the user's account
   - Or update transaction status from "pending" to "completed"

## Monitoring Deposits

### Option 1: Trezor Suite
- Open Trezor Suite
- Check transaction history
- Match amounts to user deposits

### Option 2: Blockchain Explorer
- Bitcoin: https://blockchain.info/address/YOUR_BTC_ADDRESS
- Ethereum: https://etherscan.io/address/YOUR_ETH_ADDRESS
- Monitor for incoming transactions

### Option 3: API Integration (Future)
- Integrate with BlockCypher or similar service
- Set up webhooks for automatic deposit detection
- Still requires manual verification before crediting

## Best Practices

1. **Use Separate Addresses**: Consider using different addresses for different purposes
2. **Regular Monitoring**: Check for deposits regularly
3. **Verify Before Crediting**: Always verify transactions before crediting user accounts
4. **Keep Trezor Safe**: Store your Trezor device in a secure location
5. **Backup Recovery Seed**: Ensure your recovery seed is safely backed up

## Troubleshooting

### Address Not Working?
- Verify the address is correct in `.env` file
- Check that you're using the correct network (BTC, ETH, etc.)
- Ensure the address format matches the currency

### Can't See Deposits?
- Check blockchain explorer for the address
- Verify transaction confirmations
- Check Trezor Suite transaction history

### Need to Change Address?
- Generate new address from Trezor
- Update `.env` file
- Restart application

## Next Steps

1. ✅ Set up your Trezor addresses
2. ✅ Add addresses to `.env` file
3. ✅ Test deposit flow
4. ⏳ Set up monitoring system
5. ⏳ Create admin interface for deposit verification
6. ⏳ Consider API integration for automated monitoring
