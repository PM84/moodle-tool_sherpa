moodle-tool_sherpa
=============================

*moodle-tool_sherpa* is a plugin that allows administrators to add links to supporting materials directly into Moodle. Users can then click on existing question mark icons in Moodle and receive not only text information but also links to further materials. An AI chatbot is also available.



Requirements
------------

This plugin requires Moodle 5.3+



Motivation for this plugin
--------------------------

You have extensive support materials, but you're not sure if your users can find them? Wouldn't it make sense to integrate them directly into Moodle? With *moodle-tool_sherpa*, external links to Moodle help texts can be added to simplify access and increase the visibility of these materials.

Once the plugin is installed, the dialog box will contain not only the standard help text, but also links to additional resources and an AI chatbot.



Installation
------------

Install the plugin like any other plugin to folder `public/admin/tool/sherpa`

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------

After installing the plugin, it does not do anything to Moodle yet.

To configure the plugin and its behaviour, please visit:
Site administration -> General -> tool_sherpa

There, you find the following settings:

- "Enable Sherpa": Basic switch to enable the sherpa functionality
- "Open help in a modal": Clicking a core help icon opens a Sherpa modal instead of the popover.
- "Show further materials": The Sherpa modal lists further materials (sources) configured for the help topic.
- "Show AI chat": The Sherpa modal embeds the AI chat (when AI is available for the user).
- "Enable 'Help, what should I do?'": Teachers see a "Help, what should I do?" option in the activity chooser that opens an AI assisted activity chooser. Depends on "AI chat".
- "System prompt template": emplate for the field specific system prompt sent to the AI chat. Depends on "AI chat".

Capabilities
-----------

The plugin has two capabilities:

- `tool/sherpa:manage`: This capability has to be assigned in System context to provide access to the "Manage sources and placements" admin page.
- `tool/sherpa:usesupport`: Assign this capability to the roles which should be able to see the support materials.

If you want to learn more about using [plugin type] plugins in Moodle, please see https://docs.moodle.org/en/[plugintype].
