# QPay WooCommerce Payment Gateway

QPay V2 payment gateway plugin for WooCommerce.

## Install

1. Upload `qpay-woocommerce` folder to `/wp-content/plugins/`
2. Activate plugin in WordPress
3. Go to WooCommerce > Settings > Payments > QPay
4. Enter API credentials

## Configuration

- **API Base URL**: `https://merchant.qpay.mn`
- **Username**: QPay merchant username
- **Password**: QPay merchant password
- **Invoice Code**: QPay invoice code
- **Callback URL**: Auto-generated webhook URL

## Features

- QR code checkout display
- Bank app deeplinks
- Auto payment polling (3 second interval)
- Order status auto-update on payment
- Webhook callback support

## License

MIT
