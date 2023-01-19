# Content-Processor Plugin for Moodle (BETA version)

This plugin enables a [Moodle](https://moodle.org/) instance to utilize the **Content-Processor** for creating content from texts. The Content-Processor is developed by [QuizCo](https://quizco.de/).

**Note:** The plugin currently serves German only, that means that both the interface as well as the Content-Processer display and process German contents. Some texts are translated to English for administrative purposes.

## Installation and Configuration

Once you have acquired a license key for the Content-Processor, getting the plugin up and running is pretty easy.

### Installation

Download the latest release from the [Releases page](https://github.com/QuizCo-GmbH/moodle-plugin/releases) of this repository. The included file **content_processor.zip** can be installed in your Moodle instance according to [the official instructions](https://docs.moodle.org/401/en/Installing_plugins#Installing_via_uploaded_ZIP_file) or by following these steps:

1. When having the necessary privileges, navigate to _Administration_ → _Site administration_ → _Plugins_ → _Install plugins_
2. Make sure the ZIP file is named exactly "**content_processor.zip**". Upload it using the file selection or drag-and-drop area.
3. Begin the installation with the _Install plugin_ button.

If the plugin _does not_ install normally double-check that you have the privileges to install plugins in your instance and that your target directory is writable. Also pay attention to the plugin validation report or other warnings if something goes wrong.
Your administrator might also be able to help.

Continue with the configuration when the plugin has been installed successfully.

### Configuration

A license key is required to have access to the Content-Processor when using the plugin.

1. Add the plugin as a new **block**. The plugin automatically prompts you to enter your license key and navigates you to its settings. (You can also navigate manually to the plugin settings: go to _Administration_ → _Site administration_ → _Plugins_ → _Blocks_ → _Content-Processor_.)
1. Enter your key in the _license key_ text field and save your changes.

That's it! You're ready to go.

## Basic Usage

Enter the plugin UI by clicking on the presented link in the plugin block. Then, using the Content-Processor is easy:

1. When the plugin is ready, enter your text into the text field. If you want, check/uncheck the content types you want/don't want to have generated.
2. Confirm and kick-off the content generation. When finished, various questions of different types are displayed. Edit or remove any of them as needed.
3. You can use the generated content in two ways: **PRINT** it or **SAVE** it in Moodle's question bank.

    a. **Print**: Click on the print buttons for a variant either _with_ or _without_ the solutions. This will create a text file which you can download and then print.

    b. **Save**: Click on the button and choose the target category from your Moodle site.

Done! If you saved your content in Moodle, you can access it from within the question bank, e.g. to create a quiz.