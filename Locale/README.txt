The .pot (template) file contains the translated strings.
Copy them to your existing .po file in
/app/Locale/[language]/LC_MESSAGES/tools.po

For some languages there might be already out of the box translations available.
If not, you can send them to me and I will add them.

Note: There are no .po files in /Plugin/Locale/[language]/ as there is a core issue
with overwriting plugin translations via app. So I didn't add them in the first place
to prevent this.
So please make sure you have your app/Locale/ subfolders all set up accordingly.