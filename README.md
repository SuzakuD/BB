# Modern E-Commerce System

A fully functional e-commerce system built with PHP, JavaScript, and Bootstrap. Features include instant product search, editable shopping cart, complete product management, and a comprehensive admin panel.

## Features

### Customer Features
- **Instant Product Search**: Real-time search with instant results
- **Editable Shopping Cart**: Add, remove, update quantities, and clear cart
- **Fixed Account Dropdown**: User account management with dropdown navigation
- **Product Browsing**: Browse products by category with filtering
- **Responsive Design**: Mobile-friendly interface

### Admin Features
- **Dashboard**: Overview of products, orders, users, and revenue
- **Product Management**: Add, edit, delete products with image uploads
- **Order Management**: View and manage customer orders
- **User Management**: Manage customer accounts
- **Category Management**: Organize products by categories

## Installation

1. **Requirements**
   - PHP 7.4 or higher
   - SQLite3 extension
   - Web server (Apache/Nginx)

2. **Setup**
   ```bash
   # Clone or download the project
   cd ecommerce-system
   
   # Set proper permissions
   chmod 755 data/
   chmod 755 public/images/
   
   # Access the application in your browser
   # The database will be automatically initialized on first visit
   ```

3. **Default Admin Account**
   - Username: `admin`
   - Password: `admin123`

## File Structure

```
├── index.php              # Main application file
├── config/
│   └── database.php       # Database configuration
├── public/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   ├── js/
│   │   └── app.js         # Main JavaScript application
│   └── images/
│       └── products/      # Product images
├── pages/                 # Page templates
│   ├── home.php
│   ├── products.php
│   ├── login.php
│   └── register.php
├── admin/                 # Admin page templates
│   ├── dashboard.php
│   └── products.php
├── api/                   # API endpoints
│   ├── search.php
│   ├── cart.php
│   ├── products.php
│   ├── categories.php
│   ├── login.php
│   ├── register.php
│   ├── user-status.php
│   ├── logout.php
│   └── admin/             # Admin API endpoints
│       ├── dashboard.php
│       ├── products.php
│       ├── save-product.php
│       └── delete-product.php
└── data/                  # Database and data files
    ├── app.db            # SQLite database (auto-created)
    └── init_db.php       # Database initialization
```

## Usage

### For Customers
1. Browse products by category
2. Use instant search to find products
3. Add products to cart
4. Manage cart quantities
5. Proceed to checkout (requires login)

### For Admins
1. Login with admin credentials
2. Access admin panel from sidebar
3. Manage products, orders, and users
4. View dashboard statistics

## API Endpoints

- `GET /api/products.php` - Get all products
- `GET /api/search.php?q=query` - Search products
- `GET /api/categories.php` - Get all categories
- `POST /api/cart.php` - Get cart data
- `POST /api/login.php` - User login
- `POST /api/register.php` - User registration
- `GET /api/user-status.php` - Get current user status
- `POST /api/logout.php` - User logout

### Admin Endpoints
- `GET /api/admin/dashboard.php` - Get dashboard data
- `GET /api/admin/products.php` - Get all products for admin
- `POST /api/admin/save-product.php` - Save/update product
- `POST /api/admin/delete-product.php` - Delete product

## Security Features

- Password hashing with PHP's built-in functions
- Session-based authentication
- Admin role verification
- Input validation and sanitization
- SQL injection prevention with prepared statements

## Customization

- Modify `public/css/style.css` for styling changes
- Update `public/js/app.js` for JavaScript functionality
- Add new API endpoints in the `api/` directory
- Create new page templates in the `pages/` directory

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

This project is open source and available under the MIT License.
