# Absensi HTTPS & Cloudinary Setup Guide

## ✅ Completed Automated Setup

I've already completed the following:

1. ✅ **Installed mkcert** - Local HTTPS certificate authority
2. ✅ **Generated HTTPS certificates** - `absensi.test+2.pem` and `absensi.test+2-key.pem` in `admin-dashboard-app/`
3. ✅ **Updated Next.js config** - Added `dev:https` npm script for HTTPS development server
4. ✅ **Created Cloudinary integration** - Added `cloudinary-upload.php` with fallback to local storage
5. ✅ **Updated absensi_api.php** - Now uses Cloudinary for image uploads (with local fallback)
6. ✅ **Created .env.local** - Template with Cloudinary configuration

---

## 📋 Your Next Steps

### **Step 1: Configure Cloudinary (REQUIRED for public image URLs)**

1. **Sign up for Cloudinary**: https://cloudinary.com (free tier is sufficient)

2. **Get your credentials** from the Dashboard:
   - Cloud Name
   - API Key
   - API Secret

3. **Update `.env.local`** in `admin-dashboard-app/`:
   ```env
   NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME=your_cloud_name_here
   CLOUDINARY_API_KEY=your_api_key_here
   CLOUDINARY_API_SECRET=your_api_secret_here
   ```

4. **Also create/update `.env` or `.env.php` in root** for PHP to access credentials:
   - Option A: Set as system environment variables
   - Option B: Create `.env.php` in root directory with:
   ```php
   <?php
   putenv('CLOUDINARY_CLOUD_NAME=your_cloud_name_here');
   putenv('CLOUDINARY_API_KEY=your_api_key_here');
   putenv('CLOUDINARY_API_SECRET=your_api_secret_here');
   ```

---

### **Step 2: Configure Windows Hosts File**

Add `absensi.test` to your Windows hosts file:

1. **Open Notepad as Administrator**
2. **Open**: `C:\Windows\System32\drivers\etc\hosts`
3. **Add this line**:
   ```
   127.0.0.1  absensi.test
   ```
4. **Save and close**

---

### **Step 3: Run Next.js with HTTPS**

Navigate to `admin-dashboard-app/` and run:

```bash
npm run dev:https
```

This starts the dev server on `https://localhost:3000` with the certificates.

---

### **Step 4: Access the Application**

**Camera will now work on:**
- ✅ `https://absensi.test/admin-stats/` (HTTPS)
- ✅ `http://localhost/Absensi/` (localhost)

**Charts work on:**
- ✅ `https://absensi.test/` (HTTPS)

**WhatsApp images will be:**
- ✅ Public URLs from Cloudinary (e.g., `https://res.cloudinary.com/xxx/image/upload/v123/absensi/...`)

---

## 🔍 How It Works

### **HTTPS for Camera**
- Browsers restrict camera access to HTTPS or localhost
- `mkcert` created local certificates that your browser trusts
- `https://absensi.test` is now a secure context ✅

### **Cloudinary for WhatsApp**
- Images uploaded to your Cloudinary account
- Returns public HTTPS URLs
- WhatsApp can access and display images from any device ✅

### **Fallback**
- If Cloudinary is not configured, images save locally as before
- No breaking changes to existing functionality

---

## ⚠️ Important Notes

1. **Certificate Trust**: The mkcert certificate is automatically trusted on your machine (browser warning is gone after mkcert install)

2. **Cloudinary Free Tier Limits**:
   - 25 GB storage
   - 25 GB bandwidth/month
   - Should be sufficient for testing/small deployments

3. **Environment Variables**:
   - `.env.local` is for Next.js frontend
   - PHP also needs access to Cloudinary credentials (set via putenv or system env vars)

4. **Browser Cache**:
   - Clear browser cache if you see old localhost URLs in WhatsApp messages

5. **Troubleshooting**:
   - If camera still doesn't work: Check browser console (DevTools F12) for errors
   - If images don't upload to Cloudinary: Check PHP error logs, Cloudinary credentials
   - If charts don't load: Make sure API base is using correct protocol (HTTPS)

---

## 🧪 Testing Checklist

- [ ] Access `https://absensi.test/` - No browser warning (certificate trusted)
- [ ] Open camera on absensi.test - Camera works (not just fallback)
- [ ] Charts load on absensi.test
- [ ] Upload image - Verify it goes to Cloudinary (check browser Network tab)
- [ ] Share to WhatsApp - Image URL is from `res.cloudinary.com`, not localhost
- [ ] Download from WhatsApp - Image loads correctly

---

## 📞 If You Need Help

When you've set up Cloudinary credentials and encounter issues:
1. Check browser DevTools Console (F12) for errors
2. Check PHP error logs for upload issues
3. Verify Cloudinary credentials in `.env.local`
4. Test Cloudinary upload directly if needed

You're ready to proceed! Let me know once you've set up Cloudinary and I can help with any issues.
