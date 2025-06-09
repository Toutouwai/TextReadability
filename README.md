# Text Readability

A module that uses the [PHP Text Statistics](https://github.com/DaveChild/Text-Statistics) class to evaluate the readability of English text in textarea fields according to various tests.

The available readability tests are:

* [Flesch Kincaid Reading Ease](https://readable.com/readability/flesch-reading-ease-flesch-kincaid-grade-level/)
* [Flesch Kincaid Grade Level](https://readable.com/readability/flesch-reading-ease-flesch-kincaid-grade-level/)
* [Gunning Fog Index](https://readable.com/readability/gunning-fog-index/)
* [SMOG Index](https://readable.com/readability/smog-index/)
* [Automated Reability Index](https://readable.com/readability/automated-readability-index/)
* [Spache Readability Score](https://readable.com/readability/spache-readability-formula/)
* [Dale Chall Readability Score](https://readable.com/readability/new-dale-chall-readability-formula/)
* [Coleman Liau Index](https://readable.com/readability/coleman-liau-readability-index/)

![Image](https://github.com/user-attachments/assets/ab9640a5-87dc-4929-87d5-44719f1db235)

The results of the enabled tests are displayed at the bottom of textarea fields – either when the "book" header icon is clicked, or at all times, depending on the option selected in the module configuration.

Requires ProcessWire >= 3.0.246 and PHP >= 7.2.0

## Configuration

* Select which readability tests you want to enable. For each test there is an "about" link to information about the test.
* Select whether the results of the enabled readability tests should be shown only when the header action icon is clicked (default), or if the results should always be shown.
* For multi-language sites, select which ProcessWire language represents English (as the tests are only intended for English text).
