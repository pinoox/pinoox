# Release Notes
## [Unreleased](https://github.com/pinoox/pinoox/compare/1.2.1b3...master)

## [v1.6.8 (2021-11-16)](https://github.com/pinoox/pinoox/compare/1.6.8...master)  

## Added

- Add a quick connection to a Pinoox account
- Add beautiful dumb component
- Add template installation in the manual installer
- Add the ability to upload base64 files

## Changed

- Improved performance of the controllers under the folder
- Upgrade Uploader Component
- Improving HTTP Request component in curl method

## Fixed

- Fix a problem with installing Pinoox on version older MySQL
- Fix result validation generate
- Fix date problem in the validation component
- Fix an issue with receiving meta information in the templates list
- Fix an authorization recognition problem on some web servers
- Minor bug fixes
 
 
## [v1.6.0 (2020-12-28)](https://github.com/pinoox/pinoox/compare/1.6.0...master)  

 ## Added
 
 - Optimized with PHP 8
 
 ## Changed
 
 - Optimize template installation
 
 ## Fixed
 
 - Fixed move to the welcome page after installation
 - Fixed show active template after activation
 - Fixed implode problem in DB component
 - Fixed the problem of checking the Public method on the router
 
 
## [v1.5.8 (2020-12-20)](https://github.com/pinoox/pinoox/compare/1.5.8...master)   

## Added

- Load PHP file when running an application
- Get a list of templates in the template component
- Insert template information in the template component

## Changed

- Improved thumbnail creation in the upload component

## Fixed

- Problem recognizing the HTTPS protocol
- Fixed the problem of displaying the cover image of the templates


## [v1.5.5 (2020-09-13)](https://github.com/pinoox/pinoox/compare/1.5.5...master)   

## Added

- Add tabs and improved navigation in the manager
- Add app preview privately in the manager (secret view)
- Add template management 
- Add dashboard app
- Add notifications
- Add drag & drop for manual installation packages templates
- Add Pinoox Baker (pinker) for easier app management & development
- Add floating Installer 
- Add sidebar component in manager for managing menus
- Add download and install templates from market 

## Changed

- Improved the installation and update process
- Improved market UI/UX
- Upgrade File, Zip, Lang, and Upload components
- Improved downloader in the market
- Improved progress bar in manager

## Fixed

- Fixed bug headers HttpRequest component
- Fixed static text title and use Lang system instead
- Fixed alert messages text 
- Fixed routes in app manager
- Fixed bug for scrolling app details in the market


## [v1.4.6 (2020-05-19)](https://github.com/pinoox/pinoox/compare/1.4.6...master)   

## Added

- Add Karla font for English
- Add multiple parametric routes to routing

## Changed

- Optimize Pinoox installation file (reduce size)
- Ability to support emojis in database with UTF8MB4

## Fixed

- Fixed the user's session lifetime
- Fixed database problems in some systems with limited resources
- Fixed the installer problem in some devices to check the required resources
- Fixed the problem of displaying images on iOS
- Fixed some minor problems...

## [v1.4.0 (2019-12-28)](https://github.com/pinoox/pinoox/compare/1.4.0...master)   

## Added

- Connect pinoox account to market
- Add app management section
- Ability to change the configuration of application
- Ability to hide the app in the desktop dock
- Add a Live Wallpaper (animated)
- Add new wallpapers
- Ability to see users of an app

## Changed

- Changed the user authentication to JWT method and improved security
- Redesigned and improved the app market
- Increased market loading speed
- Optimized the application installation process

## [v1.2.1b3 (2019-08-30)](https://github.com/pinoox/pinoox/compare/1.2.1b3...master)   

## Added

- English language

## Changed

- Ability to preview apps without having to define the route
- Ability to update apps from the market
- Displays the loading on the login and lock screen
- Optimized the application installation process
- Improved user interface


## Fixed

- Fixed delay problem in first run and optimize cache process
- Fixed redirect problem after login
- Fixed market load apps list because of AJAX cross domain blocking 
- Fixed page refresh after each update
- Fixed timezone issue in php versions that do not have the default timezone
- Fixed web servers that have PUT, DELETE, PATCH methods locked