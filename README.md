# ร้านอุปกรณ์ตกปลา - E-commerce SPA

A full-featured e-commerce Single Page Application (SPA) for selling fishing equipment, built with PHP 8, SQLite, and Vanilla JavaScript.

## 🎣 Features

### Customer Features
- **Product Catalog**: Browse fishing equipment by categories
- **Search**: Find products by name or description
- **Shopping Cart**: Add/remove items, update quantities
- **User Authentication**: Register and login system
- **Checkout Process**: Complete order processing with promotion codes
- **Order History**: View past orders
- **Receipt Generation**: Printable receipts for orders

### Admin Features
- **Product Management**: CRUD operations for products
- **Category Management**: Manage product categories
- **User Management**: Manage customer accounts
- **Order Management**: View and update order status
- **Promotion Management**: Create and manage discount codes
- **Reports Dashboard**: Sales and inventory reports

### Technical Features
- **Single Page Application**: No full page reloads
- **AJAX/Fetch API**: All interactions via JSON APIs
- **Modal-based UI**: Login, cart, checkout as modals
- **Responsive Design**: Bootstrap-powered responsive layout
- **SQLite Database**: Lightweight, file-based database
- **Session Management**: PHP session-based authentication
- **Security**: PDO prepared statements, password hashing

## 🛠️ Tech Stack

- **Backend**: PHP 8 with PDO
- **Database**: SQLite
- **Frontend**: Vanilla JavaScript, Bootstrap 5
- **Icons**: Font Awesome
- **Database Tool**: Adminer (included)

## 📁 Project Structure

```
/
├── index.php                 # Main SPA entry point
├── config/
│   └── database.php         # Database connection and helpers
├── api/                     # API endpoints
│   ├── auth.php            # Authentication API
│   ├── products.php        # Products API
│   ├── cart.php            # Shopping cart API
│   ├── orders.php          # Orders API
│   ├── receipt.php         # Receipt printing
│   └── admin/              # Admin APIs
│       ├── users.php       # User management
│       ├── promotions.php  # Promotion management
│       └── reports.php     # Reports and analytics
├── public/                 # Static assets
│   ├── css/
│   │   └── style.css       # Custom styles
│   ├── js/
│   │   └── app.js          # Main JavaScript application
│   └── images/             # Product images
├── data/                   # Database and scripts
│   ├── app.db              # SQLite database
│   ├── init_db.php         # Database initialization
│   └── seed.php            # Sample data seeder
└── tools/
    ├── adminer.php         # Database management tool
    └── adminer_config.php  # Adminer configuration
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- SQLite extension enabled
- Web server (Apache/Nginx) or PHP built-in server

### Quick Start

1. **Clone/Download** the project files to your web server directory

2. **Initialize Database**:
   ```bash
   php data/init_db.php
   ```

3. **Seed Sample Data**:
   ```bash
   php data/seed.php
   ```

4. **Start the Application**:
   - For development: `php -S localhost:8000`
   - For production: Configure your web server to serve `index.php`

5. **Access the Application**:
   - Main site: `http://localhost:8000`
   - Database admin: `http://localhost:8000/tools/adminer.php`

## 👤 Default Accounts

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`
- **Role**: Administrator (full access)

### Test Promotion Codes
- **NEWBIE10**: 10% discount
- **FISHING20**: 20% discount  
- **WEEKEND15**: 15% discount

## 🗄️ Database Schema

### Tables
- **users**: User accounts and roles
- **categories**: Product categories
- **products**: Product catalog
- **orders**: Customer orders
- **order_items**: Order line items
- **promotions**: Discount codes
- **receipts**: Generated receipts

### Key Features
- Foreign key relationships
- Check constraints for data integrity
- Automatic timestamps
- Password hashing

## 🎯 Usage Guide

### Customer Workflow
1. Browse products or search by category
2. Add items to shopping cart
3. Register/Login (required for checkout)
4. Review cart and apply promotion codes
5. Complete checkout process
6. View and print receipt

### Admin Workflow
1. Login with admin account
2. Access admin panel from sidebar
3. Manage products, categories, users
4. Process orders and update status
5. Create promotional campaigns
6. View sales reports and analytics

## 🔧 API Endpoints

### Authentication
- `POST /api/auth.php?action=login` - User login
- `POST /api/auth.php?action=register` - User registration
- `DELETE /api/auth.php?action=logout` - User logout
- `GET /api/auth.php?action=check` - Check auth status

### Products
- `GET /api/products.php?action=list` - List products
- `GET /api/products.php?action=categories` - List categories
- `GET /api/products.php?action=search&q={query}` - Search products
- `GET /api/products.php?action=category&category_id={id}` - Filter by category

### Shopping Cart
- `POST /api/cart.php?action=add` - Add item to cart
- `GET /api/cart.php?action=items` - Get cart contents
- `PUT /api/cart.php?action=update` - Update item quantity
- `DELETE /api/cart.php?action=remove` - Remove item

### Orders
- `POST /api/orders.php?action=checkout` - Process checkout
- `GET /api/orders.php?action=detail&id={order_id}` - Order details
- `GET /api/orders.php?action=receipt&order_id={id}` - Get receipt

## 🎨 Customization

### Adding New Product Categories
1. Use admin panel or direct database insert
2. Update category icons in CSS if needed
3. Categories appear automatically in sidebar

### Styling Modifications
- Edit `public/css/style.css` for custom styles
- Bootstrap classes can be overridden
- Responsive breakpoints are predefined

### Adding New Features
- Create new API endpoints in `/api/`
- Add corresponding JavaScript functions
- Update UI components as needed

## 🔒 Security Features

- **SQL Injection Protection**: PDO prepared statements
- **Password Security**: PHP password_hash() and password_verify()
- **Session Management**: Secure PHP sessions
- **Input Validation**: Server-side validation for all inputs
- **CSRF Protection**: Session-based authentication
- **Role-based Access**: Admin vs user permissions

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check SQLite extension is enabled
   - Verify file permissions on `data/` directory
   - Run `php data/init_db.php` to recreate database

2. **Admin Panel Not Showing**:
   - Ensure you're logged in as admin role
   - Check browser console for JavaScript errors
   - Verify admin APIs are accessible

3. **Cart Not Working**:
   - Check PHP session configuration
   - Verify AJAX calls in browser network tab
   - Clear browser cookies/session

4. **Receipt Printing Issues**:
   - Check popup blocker settings
   - Verify `api/receipt.php` is accessible
   - Test with different browsers

## 📱 Mobile Support

The application is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- Touch devices

## 🔄 Updates & Maintenance

### Regular Tasks
- Monitor database size and performance
- Update promotion codes
- Review and process orders
- Check inventory levels
- Update product information

### Backup Recommendations
- Regular backup of `data/app.db`
- Export important data periodically
- Test restore procedures

## 📞 Support

For technical support or feature requests:
- Check the troubleshooting section
- Review API documentation
- Examine browser console for errors
- Test with different browsers/devices

## 📄 License

This project is created for educational and demonstration purposes. Feel free to modify and adapt for your needs.

---

**Happy Fishing! 🎣**