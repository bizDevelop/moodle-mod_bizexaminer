# bizExaminer #

bizExaminer is a software solution to create, conduct and manage online exams.
Our Moodle plugin allows you to include exams in your courses.

bizExaminer is a **complete** and stable solution for stress-free examination.  
**Important:** You need a bizExaminer account to use this plugin. You can request a free demo on [our website](https://www.bizexaminer.com/#demo). 

## Features of bizExaminer ##

- Integration of various **remote proctoring** solutions.
- Over 20 question types.
- Extensive options for exam configuration.
- Lockdown Client for Windows and Mac and SafeExamBrowser fully integrated.
- As cloud solution or own server.
- Preview and "tryout" questions and exams for authors.
- Advanced content administration.
- Reliable and stable in case of technical problems during an exam.
- [And much more...](https://www.bizexaminer.com/features/)

https://www.youtube.com/watch?v=PkGmyH4JEIQ

## Integrate bizExaminer into Moodle ##

With our Moodle plugin you can use all of the LMS features and handle exams in bizExaminer.

- bizExaminer Exams are available as an **activity module** inside your Moodle courses.
- Integrates with Moodles **grading**, and supports multiple grade methods (highest, first, last attempt, average).
- **Remote Proctoring:** bizExaminer supports Examity, Constructor (Examus), ProctorExam, Proctorio and Meazure Learning (ProctorU) for monitoring remote exams.
- **Results** are directly stored in Moodle and therefore available to show in attempt details.
- Use **bizExaminer certificates** which are shown to users when they pass the exams.
- Configure **restrictions** for exams like:
  - Open- and closing time
  - Password
  - IP-address
  - Maximum attempts
  - Delay between attempts
- Integrates with Moodles **privacy API**.
- Implements Moodle backup API.

Do you use special Moodle features, other third-party plugins or have any **feature requests**? Let us know!

**Info:** 
Our plugin currently only supports registered users (no guests). Also the grading type "scale" is not supported at the moment.

## Planned features ##

- Support scale grading type.
- Map competencies to questions/sections in bizExaminer.
- Add more Moodle log events.
- Easier access to manual evaluation in bizExaminer.
- Add multiple API credentials (multiple content owners).
- Implement Moodle backup API.

## Support ##

### Need help? ###

You may ask your questions regarding the bizExaminer Moodle plugin in the comments in the Moodle plugin directory.
Professional help desk support is being offered to bizExaminer customers only.

Also visit our [support page](https://support.bizexaminer.com/article/using-the-bizexaminer-moodle-plugin/)

### Want to file a bug or improve the bizExaminer Moodle plugin? ###
Bug reports may be filed via the comments in the Moodle plugin directory. If you have found security vulnerability, please [contact us](https://www.bizexaminer.com/contact/) immediately.

## Installation ##

### Installing via uploaded ZIP file ###

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code.
3. Check the plugin validation report and finish the installation.

### Installing manually ###

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/bizexaminer

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

### Setup ###

#### Prerequisites ####

- Create API credentials in your bizExaminer Account

#### Configuration ####

1. [Install plugin](#installing-manually)
2. After the installation you will be presented with the new settings or you can manually go to Administration > Plugins > bizExaminer
3. Log into your bizExaminer instance as administrator and go to "Settings" > "Owner" / "Organisation" to copy your API credentials.
4. Configure API Credentials
   1. Enter the Instance Domain for your bizExaminer instance, i.e. yourcompany.bizexaminer.com, without the www/http.
   2. Enter your bizExaminer API credentials by copy/pasting them into the API Key Owner and API Key Organization fields. If you don’t have them, reach out to bizExmainer support here.
   3. Optional: You can test your credentials by clicking “Test”. The page will reload, and you will get a success or error message. You can also check if your credentials are valid by going to the moodle admin checks page.

## License ##

2023 bizExaminer <moodle@bizexaminer.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.

## Changelog ##

### 1.4.0 (2024-05-02) ###
- Compatibility: Support Moodle 4.4 and PHP 8.3
- Enhancement: Add Meazure Learning (ProctorU) remote proctor
- Enhancement: Add Examity v5 Support
- Enhancement: Redo Remote Proctor UI to allow for more complex settings. No breaking changes - all existing remote proctor options should work as they were.
- Enhancement: Add clearer error messages for users and for logging
- Enhancement: Update translation strings to use sentence case and add more German translations
- Dev: Add moodle-plugin-ci for code-checks. Check [GitHub Repository](https://github.com/bizDevelop/moodle-mod_bizexaminer/actions) for linting status of all our releases.

### 1.3.0 (2024-03-20) ###
- Tweak: "Test credentials" button is now disabled when you have unsaved changes in the API credentials settings, to prevent you from discarding those changes
- Fix: Deprecation warnings regarding `explode` on plugin activation
- Fix: "Element exam_module does not exist" errors when starting an exam
- Fix: Switching API Credentials in exam
- Fix: Update website links
- Dev: Fix namespaces to align with moodle namespacing guide.
- Dev: Add a build process for JavaScript files.

### 1.2.0 (2024-01-11) ###
- Compatibility: Support Moodle 4.3 and PHP 8.2
- Fix: Error when changing API Credentials in existing exam
- Fix: Supported Moodle versions format in `version.php`

### 1.1.2 (2023-12-05) ###
- Fix: fix warnings on saving first api credentials
- Compatibility/Dev: Add info about supported Moodle versions in `version.php` via `supported` key (see [GitHub issue](https://github.com/bizDevelop/moodle-mod_bizexaminer/issues/1#issuecomment-1840275570))

### 1.1.1 (2023-12-04) ###
- Dev: Rename GitHub release repository and restructure to be compatible with the expected directory structure from Moodle

### 1.1.0 (2023-11-29) ###
- New: Add option to configure multiple API credentials
- Tweak: Re-Add German translations for easier testing before plugin is live in Moodle repository
- Code Quality: Apply coding standards from Moodle plugin repository
- Dev: Add GitHub release action workflow

### 1.0.1 (2023-10-05) ###
- Fix phpcs warnings/issues from moodle repository.

### 1.0.0 (2023-10-05) ###
- Prepare plugin for first public release in plugin repository.