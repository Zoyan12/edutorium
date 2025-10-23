# Troubleshooting Supabase Auth Issues

If you're seeing `Database error saving new user` when trying to sign up new users, follow these steps to identify and fix the issue:

## 1. Check Supabase Project Status

First, verify that your Supabase project is active and functioning properly:

- Go to your [Supabase Dashboard](https://app.supabase.com)
- Check if your project is in "Active" state
- Check if you're near your database limits (free tier has limitations)
- Check if there are any status notices about service disruptions

## 2. Verify Database Schema

Ensure your database schema and RLS policies are correctly set up:

- Run the `simple-db-fix.sql` script in the SQL Editor
- Check if there are any errors when running the script
- Verify that the profiles table was created with the correct columns

## 3. Check Email Configuration

If you're using email confirmations:

- Go to Authentication → Settings → Email
- Make sure your email provider is configured correctly
- Consider temporarily disabling email confirmation for testing
- Check if you're using a custom SMTP server and if it's properly configured

## 4. Try Alternative Signup Approach

We've updated the code to use a simplified signup approach:

- User metadata is now minimal during signup (only username)
- Field value is stored in localStorage
- Profile creation is done entirely during the profile completion step
- This approach reduces database writes during the auth process

## 5. Check for Duplicate Usernames

The error can sometimes occur when trying to create a user with a duplicate username:

- Check if username uniqueness checks are working
- Look in the profiles table for any conflicts
- Try signing up with a completely different username

## 6. Check Browser Console for Additional Errors

Sometimes additional details appear in the browser console:

- Open Developer Tools (F12 or right-click → Inspect)
- Go to the Console tab
- Look for any additional error messages or warnings
- Check the Network tab for specific HTTP status codes

## 7. Database RLS Policy Issues

Sometimes restrictive RLS policies can cause auth issues:

- Go to SQL Editor and list all policies:
  ```sql
  SELECT * FROM pg_policies;
  ```
- Make sure the profiles table has an INSERT policy

## 8. Last Resort: Contact Supabase Support

If all else fails:

- Create a support ticket with Supabase
- Include your project reference ID
- Describe the issue in detail
- Mention the exact error message

## Testing Your Fix

After making changes:

1. Try signing up with a completely new email
2. Use a simple, unique username
3. Complete the profile form
4. Check if the profile was properly created

Remember that some issues may be related to Supabase's infrastructure or limits rather than your code. 