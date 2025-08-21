# Handicrafts Marketplace

A comprehensive e-commerce platform for traditional handicrafts, built with PHP, MySQL, and modern web technologies. This system connects artisans with customers, showcasing authentic handcrafted products from Bangladesh and West Bengal.

## 🌟 Features

### Customer Features
- **User Authentication**: Secure login/registration system with Google OAuth support
- **Product Browsing**: Browse products by category, artisan, or search functionality
- **Advanced Search**: Fuzzy matching with relevance scoring and search suggestions
- **Shopping Cart**: Add/remove items, quantity management, and cart persistence
- **Favorites**: Save products to wishlist for later purchase
- **Checkout Process**: Multi-step checkout with multiple payment methods
- **Order Management**: View order history, track status, and order details
- **User Profiles**: Manage personal information and addresses

### Artisan Features
- **Artisan Profiles**: Detailed artisan information with bio and specializations
- **Product Management**: Upload and manage product listings
- **Portfolio Showcase**: Display artisan's work and expertise

### Admin Features
- **Inventory Management**: Track stock quantities and product status
- **Order Processing**: Manage orders, update statuses, and process payments
- **User Management**: Monitor user accounts and activities
- **Analytics**: Track sales, popular products, and customer behavior

## 🏗️ System Architecture

### Frontend
- **HTML5**: Semantic markup with modern web standards
- **CSS3**: Responsive design with custom styling
- **JavaScript**: ES6+ with modular architecture
- **Progressive Web App**: Offline capabilities and mobile-first design

### Backend
- **PHP 8.0+**: Server-side logic and API endpoints
- **MySQL/MariaDB**: Relational database for data persistence
- **RESTful APIs**: JSON-based communication between frontend and backend
- **Session Management**: Secure user authentication and authorization

### Database Design
- **Normalized Schema**: Efficient data storage with proper relationships
- **Foreign Key Constraints**: Data integrity and referential integrity
- **Indexed Queries**: Optimized database performance
- **Transaction Support**: ACID compliance for critical operations

## 📁 Project Structure

```
handicrafts_marketplace/
├── api/                          # Backend API endpoints
│   ├── artisans.php             # Artisan management
│   ├── auth.php                 # Authentication & authorization
│   ├── cart.php                 # Shopping cart operations
│   ├── categories.php           # Product categories
│   ├── favorites.php            # User favorites/wishlist
│   ├── orders.php               # Order processing
│   ├── products.php             # Product management
│   ├── profile.php              # User profile management
│   └── search_helpers.php       # Search functionality
├── assets/                       # Static assets
│   ├── css/                     # Stylesheets
│   ├── images/                  # Product and UI images
│   └── js/                      # JavaScript modules
├── config/                       # Configuration files
│   └── database.php             # Database connection
├── database/                     # Database files
│   └── handicrafts_marketplace.sql  # Database schema
├── includes/                     # Shared PHP functions
│   └── functions.php            # Utility functions
├── uploads/                      # User uploads
│   └── profile_photos/          # User profile images
└── *.html                       # Frontend pages
```

## 🎥 Demo Video

Watch our system demonstration to see the Handicrafts Marketplace in action:

📺 **[Watch Demo Video](https://drive.google.com/file/d/15DtsbjgHG3TsW4T-A3Ms3ysN7Lf4mwtj/view?usp=sharing)**

The demo showcases:
- User registration and authentication
- Product browsing and search functionality
- Shopping cart and checkout process
- Order management and payment processing
- Artisan profile management
- Admin dashboard features

---

## 🚀 Installation & Setup

### Prerequisites
- **Web Server**: Apache/Nginx with PHP support
- **PHP**: Version 8.0 or higher
- **MySQL**: Version 5.7 or higher (MariaDB 10.4+ supported)
- **XAMPP/WAMP**: For local development (recommended)

### Step-by-Step Setup

1. **Clone/Download Project**
   ```bash
   git clone [repository-url]
   cd handicrafts_marketplace
   ```

2. **Database Setup**
   - Start your MySQL server
   - Create a new database: `handicrafts_marketplace`
   - Import the schema: `database/handicrafts_marketplace.sql`

3. **Configuration**
   - Edit `config/database.php` with your database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'handicrafts_marketplace';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

4. **Web Server Configuration**
   - Place project in your web server's document root
   - Ensure PHP has write permissions for `uploads/` directory
   - Configure URL rewriting if needed

5. **Test Installation**
   - Visit your project URL in a browser
   - Check for any error messages
   - Verify database connection

## 🔧 Configuration

### Environment Variables
The system uses PHP configuration files. For production, consider:
- Moving sensitive data to environment variables
- Using `.env` files for configuration
- Implementing proper security headers

### Database Configuration
- **Host**: Database server address
- **Database Name**: `handicrafts_marketplace`
- **Username**: Database user with appropriate permissions
- **Password**: Secure database password

### File Upload Settings
- **Max File Size**: Configured in PHP settings
- **Allowed Types**: Images (JPG, PNG, GIF)
- **Upload Directory**: `uploads/` with subdirectories

## 📱 Usage Guide

### For Customers

1. **Registration & Login**
   - Create account with email/password
   - Or use Google OAuth for quick access
   - Complete profile with shipping information

2. **Shopping Experience**
   - Browse products by category or search
   - Add items to cart or favorites
   - Review cart and proceed to checkout

3. **Checkout Process**
   - Fill shipping and billing information
   - Choose payment method (Card, Mobile Banking, Cash)
   - Complete order and receive confirmation

4. **Order Management**
   - View order history and status
   - Track shipping and delivery
   - Access order details and receipts

### For Artisans

1. **Profile Setup**
   - Complete artisan profile with bio and expertise
   - Upload profile image and portfolio
   - Set location and specializations

2. **Product Management**
   - Add new products with descriptions
   - Set pricing and stock quantities
   - Upload high-quality product images

### For Administrators

1. **System Management**
   - Monitor user registrations and activities
   - Manage product categories and artisan accounts
   - Process orders and update statuses

2. **Inventory Control**
   - Track product stock levels
   - Update product information and pricing
   - Manage product status (active/inactive)

## 🔒 Security Features

- **Password Hashing**: Bcrypt encryption for user passwords
- **SQL Injection Prevention**: Prepared statements and parameterized queries
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Token-based request validation
- **Session Security**: Secure session management and timeout
- **File Upload Security**: Type validation and size restrictions

## 🧪 Testing

### Test Files
- `test_db.php`: Database connection testing
- `test_auth.php`: Authentication system testing
- `test_products.php`: Product functionality testing
- `test_order_creation.php`: Order processing testing

### Testing Guidelines
1. Test user registration and login
2. Verify product browsing and search
3. Test shopping cart functionality
4. Validate checkout process
5. Test order creation and management
6. Verify payment processing

## 🚀 Deployment

### Production Checklist
- [ ] Update database credentials
- [ ] Configure HTTPS and SSL certificates
- [ ] Set up proper file permissions
- [ ] Configure error logging
- [ ] Implement backup strategies
- [ ] Set up monitoring and analytics
- [ ] Configure email services
- [ ] Test all functionality thoroughly

### Performance Optimization
- Enable PHP OPcache
- Configure MySQL query cache
- Implement CDN for static assets
- Optimize database queries
- Enable Gzip compression

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Verify database credentials
   - Check MySQL service status
   - Ensure database exists

2. **File Upload Issues**
   - Check directory permissions
   - Verify PHP upload settings
   - Check file size limits

3. **Session Problems**
   - Clear browser cookies
   - Check PHP session configuration
   - Verify session storage permissions

4. **Payment Processing Errors**
   - Check payment gateway configuration
   - Verify transaction logs
   - Test with different payment methods

### Debug Mode
Enable debug mode by setting:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📊 Database Schema

### Key Tables
- **users**: Customer accounts and profiles
- **artisans**: Artisan information and expertise
- **products**: Product listings and inventory
- **categories**: Product categorization
- **orders**: Order information and status
- **order_items**: Individual items in orders
- **cart**: Shopping cart contents
- **favorites**: User wishlist items

### Relationships
- Users can have multiple orders
- Products belong to categories and artisans
- Orders contain multiple order items
- Cart and favorites link users to products

## 🔄 API Endpoints

### Authentication
- `POST /api/auth.php` - User login
- `POST /api/auth.php?action=register` - User registration
- `POST /api/logout.php` - User logout

### Products
- `GET /api/products.php?action=all` - Get all products
- `GET /api/products.php?action=single&id={id}` - Get single product
- `GET /api/products.php?action=search&q={query}` - Search products

### Cart
- `GET /api/cart.php` - Get cart contents
- `POST /api/cart.php` - Add item to cart
- `PUT /api/cart.php` - Update cart item
- `DELETE /api/cart.php` - Remove cart item

### Orders
- `POST /api/orders.php` - Create new order
- `GET /api/orders.php` - Get user orders
- `GET /api/orders.php?order_id={id}` - Get order details

## 🤝 Contributing

### Development Guidelines
1. Follow PHP PSR standards
2. Use meaningful variable and function names
3. Add comments for complex logic
4. Test changes thoroughly
5. Update documentation as needed

### Code Style
- Use consistent indentation (4 spaces)
- Follow camelCase for variables and functions
- Use descriptive names for clarity
- Add proper error handling

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Traditional artisans and craftspeople
- Open source community
- Web development best practices
- E-commerce industry standards

## 📞 Support

For technical support or questions:
- Check the troubleshooting section
- Review error logs
- Test with different browsers/devices
- Verify system requirements

## 🔮 Future Enhancements

- **Mobile App**: Native iOS and Android applications
- **AI Integration**: Product recommendations and search
- **Multi-language Support**: Internationalization
- **Advanced Analytics**: Business intelligence dashboard
- **Payment Gateways**: Additional payment methods
- **Inventory Management**: Advanced stock tracking
- **Marketing Tools**: Email campaigns and promotions
- **Social Features**: Reviews, ratings, and sharing

---

**Last Updated**: August 2025  
**Version**: 1.0.0  
**Maintainer**: Development Team
