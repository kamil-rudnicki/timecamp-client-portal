# TimeCamp Client Portal

Simple, hacky way to share TimeCamp reports with customers.

Features:

* share reports with customers via static private url
* add your comments to reports
* control - publish reports manually
* customise - add customer logo

## Configuration

1. `composer install`
2. Put TimeCamp API Token in `index.php` config section
3. Copy [this Google Spreadsheet](https://docs.google.com/spreadsheets/d/1afZnQIwcOzyuCvrmJEisAa8dDizr1CHErllL2Ryk-hE/edit#gid=0) to your Google Drive
5. Put Google Spreadsheet CSV public URL (File->Share->Publish to the web, specific tab, CSV format) in `index.php` config section

## Build

### Local

Run `php -S localhost:8080` then open url in browser like [http://localhost:8080/?p=jan3k2!jfk3rnf](http://localhost:8080/?p=jan3k2!jfk3rnf),
where p parameter is the password you put in your Google Spreadsheet for the client in the Logo tab.

### AWS Lambda

[https://bref.sh/docs/first-steps.html](https://bref.sh/docs/first-steps.html)