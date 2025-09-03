# Fishing Gear Store - E-commerce SPA

A fully functional e-commerce Single Page Application for selling fishing equipment, built with PHP, MySQL, and vanilla JavaScript.

## Features

### Core E-commerce Features
- **Product Management**: Browse, search, and filter fishing equipment
- **Shopping Cart**: Add/remove items, update quantities, real-time totals
- **User Authentication**: Login/register with role-based access (admin/user)
- **Checkout System**: Complete order process with payment simulation
- **Order Management**: Order history, receipts, and status tracking

### Advanced Features
- **Admin Dashboard**: Complete admin panel for managing products, orders, users
- **Product Reviews**: Customer ratings and reviews system
- **Promotions**: Discount codes and promotional campaigns
- **Contact System**: Customer inquiry management
- **Responsive Design**: Mobile-friendly Bootstrap 5.3.6 interface
- **Security**: CSRF protection, password hashing, session management

### Technical Features
- **SPA Architecture**: No page reloads, AJAX-based navigation
- **Real-time Updates**: Instant cart updates, live notifications
- **Search & Filtering**: Advanced product search and filtering
- **Image Gallery**: Product image display with zoom functionality
- **Receipt Generation**: PDF/HTML receipt generation
- **Notification System**: Toast notifications for user feedback

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   git clone <repository-url>
   cd fishing-gear-store
   ```

2. **Configure Database**
   - Create a MySQL database named `fishing_store`
   - Update database credentials in `config/database.php`

3. **Initialize Database**
   ```bash
   php setup.php
   ```

4. **Set Permissions**
   ```bash
   chmod 755 assets/images/products/
   ```

5. **Access the Application**
   - Open your browser and navigate to `http://localhost/index.php`
   - Use admin credentials: `admin@fishingstore.com` / `password`

## Database Schema

The application uses the following main tables:
- `users` - User accounts and authentication
- `products` - Product catalog
- `categories` - Product categories
- `cart` - Shopping cart items
- `orders` - Order information
- `order_items` - Individual order items
- `promotions` - Discount codes and promotions
- `product_reviews` - Customer reviews and ratings
- `contact_messages` - Customer inquiries
- `notifications` - System notifications

## API Endpoints

### Authentication (`api/auth.php`)
- `POST ?action=login` - User login
- `POST ?action=register` - User registration
- `POST ?action=logout` - User logout
- `GET ?action=check` - Check authentication status

### Products (`api/products.php`)
- `GET ?action=list` - List products with pagination
- `GET ?action=get&id={id}` - Get product details
- `GET ?action=categories` - Get product categories
- `GET ?action=featured` - Get featured products
- `GET ?action=search&q={query}` - Search products

### Cart (`api/cart.php`)
- `POST ?action=add` - Add item to cart
- `GET ?action=get` - Get cart items
- `POST ?action=update` - Update cart quantity
- `POST ?action=remove` - Remove item from cart
- `POST ?action=clear` - Clear entire cart

### Orders (`api/orders.php`)
- `POST ?action=create` - Create new order
- `GET ?action=list` - Get user orders
- `GET ?action=get&id={id}` - Get order details
- `GET ?action=receipt&id={id}` - Generate receipt

### Admin (`api/admin.php`)
- `GET ?action=dashboard` - Admin dashboard stats
- `GET ?action=products` - Manage products
- `GET ?action=categories` - Manage categories
- `GET ?action=orders` - Manage orders
- `GET ?action=users` - Manage users
- `GET ?action=promotions` - Manage promotions

## File Structure

```
fishing-gear-store/
├── index.php                 # Main application entry point
├── setup.php                 # Database initialization script
├── config/
│   └── database.php          # Database configuration
├── includes/
│   └── functions.php         # Core PHP functions
├── api/                      # API endpoints
│   ├── auth.php
│   ├── products.php
│   ├── cart.php
│   ├── orders.php
│   ├── admin.php
│   └── contact.php
├── assets/
│   ├── css/
│   │   └── style.css         # Custom styles
│   ├── js/
│   │   ├── app.js            # Main application logic
│   │   ├── checkout.js       # Checkout functionality
│   │   ├── admin.js          # Admin panel
│   │   └── advanced.js       # Advanced features
│   └── images/
│       ├── placeholder.svg   # Default product image
│       └── products/         # Product images
├── database/
│   └── schema.sql            # Database schema
└── README.md
```

## Usage

### For Customers
1. **Browse Products**: Navigate through categories or use search
2. **Add to Cart**: Click "Add to Cart" on any product
3. **Manage Cart**: Update quantities or remove items
4. **Checkout**: Complete the 4-step checkout process
5. **Track Orders**: View order history and download receipts

### For Administrators
1. **Login**: Use admin credentials to access admin panel
2. **Dashboard**: View sales statistics and recent activity
3. **Manage Products**: Add, edit, or delete products
4. **Manage Orders**: Update order status and view details
5. **Manage Users**: View customer accounts
6. **Manage Promotions**: Create discount codes
7. **View Messages**: Handle customer inquiries

## Security Features

- **CSRF Protection**: All forms protected with CSRF tokens
- **Password Hashing**: Secure password storage using PHP's password_hash()
- **Session Management**: Secure session handling
- **Input Sanitization**: All user inputs sanitized
- **SQL Injection Prevention**: Prepared statements used throughout
- **Role-based Access**: Admin functions restricted to admin users

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support or questions, please contact the development team or create an issue in the repository.