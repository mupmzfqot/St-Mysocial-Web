# Google reCAPTCHA v3 Setup Guide

## Overview
This application uses Google reCAPTCHA v3 for the login page to provide invisible bot protection without user interaction.

## Prerequisites
1. Google reCAPTCHA v3 account
2. Domain registered with Google reCAPTCHA
3. Site key and secret key from Google reCAPTCHA console

## Setup Steps

### 1. Get reCAPTCHA v3 Keys
1. Go to [Google reCAPTCHA Console](https://www.google.com/recaptcha/admin)
2. Click "Create" to add a new site
3. Choose "reCAPTCHA v3"
4. Add your domain(s)
5. Accept terms and submit
6. Copy the **Site Key** and **Secret Key**

### 2. Environment Configuration
Add the following variables to your `.env` file:

```env
RECAPTCHA_SITE_KEY=your_recaptcha_site_key_here
RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key_here
RECAPTCHA_MIN_SCORE=0.5
```

**Note:** `RECAPTCHA_MIN_SCORE` is optional and defaults to 0.5. Lower values are more strict.

### 3. Frontend Configuration
The reCAPTCHA site key is automatically loaded from environment variables. Make sure to add:

```env
VITE_RECAPTCHA_SITE_KEY=your_recaptcha_site_key_here
```

### 4. How It Works
- reCAPTCHA v3 runs invisibly in the background
- When user submits login form, reCAPTCHA executes automatically
- A token is generated and sent with the login request
- Backend verifies the token with Google's API
- Login proceeds only if verification passes

### 5. Score Threshold
reCAPTCHA v3 returns a score from 0.0 to 1.0:
- **0.0**: Very likely a bot
- **1.0**: Very likely a human
- **Default threshold**: 0.5 (configurable via `RECAPTCHA_MIN_SCORE`)

### 6. Testing
- Use the test keys provided by Google for development
- Test with different user behaviors to ensure proper scoring
- Monitor logs for any verification failures

## Security Notes
- Never expose your secret key in frontend code
- The secret key is only used server-side for verification
- reCAPTCHA v3 is invisible to users, providing better UX
- Consider adjusting the score threshold based on your security needs

## Troubleshooting
- Check browser console for JavaScript errors
- Verify environment variables are loaded correctly
- Ensure domain is registered in reCAPTCHA console
- Check Laravel logs for verification failures
