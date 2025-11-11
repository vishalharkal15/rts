# 🚂 Deploy RTS Ticket System to Railway.app

## Prerequisites
- Railway.app account (sign up at https://railway.app)
- Git installed on your system
- This project files

## Deployment Steps

### 1. Initialize Git Repository (if not already done)
```bash
cd /home/vishal/Music/rts
git init
git add .
git commit -m "Initial commit - RTS Ticket System"
```

### 2. Create GitHub Repository (Optional but Recommended)
```bash
# Create a new repo on GitHub, then:
git remote add origin https://github.com/YOUR_USERNAME/rts-ticket-system.git
git branch -M main
git push -u origin main
```

### 3. Deploy to Railway

#### Option A: Deploy from GitHub (Recommended)
1. Go to https://railway.app
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your repository
5. Railway will automatically detect the Dockerfile and deploy!

#### Option B: Deploy from Local Files
1. Install Railway CLI:
```bash
npm i -g @railway/cli
# or
curl -fsSL https://railway.app/install.sh | sh
```

2. Login to Railway:
```bash
railway login
```

3. Initialize and deploy:
```bash
cd /home/vishal/Music/rts
railway init
railway up
```

4. Link to a project:
```bash
railway link
```

### 4. Configure Environment (if needed)
Railway automatically handles the deployment. The app uses JSON database, so no MySQL configuration needed!

### 5. Access Your Deployed App
After deployment completes, Railway will provide a URL like:
```
https://your-app-name.railway.app
```

## 🎯 What's Deployed?

### ✅ Included Features:
- PHP 8.2 with Apache
- JSON file-based database (no external DB needed)
- All ticket management features
- Live chat system
- Admin panel
- User authentication

### 🔐 Default Admin Credentials:
- Email: `admin@rts.com`
- Password: `admin123`
- **⚠️ IMPORTANT: Change this password after first login!**

## 📝 Post-Deployment Steps

### 1. Test the Application
Visit your Railway URL and test:
- Login functionality
- Ticket creation
- Admin panel access
- Chat system

### 2. Secure Your Application
Update the admin password immediately:
1. Login as admin
2. Go to Admin Panel
3. Delete old test accounts
4. Create your own admin account with a strong password

### 3. Monitor Your App
- Check Railway dashboard for logs
- Monitor resource usage
- Set up custom domain (optional)

## 🔧 Troubleshooting

### Issue: Database not persisting
**Solution:** Railway provides persistent volumes. Make sure the database_json directory has proper permissions (777 in Dockerfile).

### Issue: 500 Internal Server Error
**Solution:** Check Railway logs:
```bash
railway logs
```

### Issue: Sessions not working
**Solution:** Ensure PHP session directory is writable. This is handled in the Dockerfile.

## 🚀 Advanced Configuration

### Add Custom Domain
1. Go to Railway project settings
2. Click "Domains"
3. Add your custom domain
4. Update DNS records as instructed

### Scale Your Application
Railway automatically scales based on usage. You can configure:
- Memory limits
- CPU allocation
- Replica count

### Add MySQL Database (Optional)
If you want to switch from JSON to MySQL:

1. Add MySQL service in Railway:
   - Click "New" → "Database" → "Add MySQL"

2. Update `config.php`:
   - Comment out JSON config
   - Uncomment MySQL config
   - Use Railway environment variables:
```php
$DB_HOST = getenv('MYSQL_HOST') ?: 'localhost';
$DB_USER = getenv('MYSQL_USER') ?: 'root';
$DB_PASS = getenv('MYSQL_PASSWORD') ?: '';
$DB_NAME = getenv('MYSQL_DATABASE') ?: 'rts_ticket_system';
```

3. Railway will automatically inject the MySQL connection variables.

## 📊 Project Structure for Railway

```
rts/
├── Dockerfile              # Docker container configuration
├── railway.json           # Railway deployment config
├── apache-config.conf     # Apache web server config
├── docker-compose.yml     # For local Docker testing
├── config.php             # Database configuration
├── config_json.php        # JSON database implementation
├── database_json/         # Persistent data storage
├── system/                # Chat logs storage
└── [all other PHP files]  # Application code
```

## 🎉 You're All Set!

Your RTS Ticket System is now deployed on Railway.app with:
- ✅ Zero-downtime deployments
- ✅ Automatic HTTPS
- ✅ Scalable infrastructure
- ✅ Built-in monitoring
- ✅ Easy rollbacks

Visit your Railway dashboard to manage your deployment!

## 📞 Support

For Railway-specific issues, check:
- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway

For RTS application issues, check the logs in Railway dashboard.

---

**Note:** The JSON database is suitable for small to medium deployments. For high-traffic production use, consider migrating to MySQL using the optional configuration above.
