@tool @tool_sherpa @javascript
Feature: Sherpa help chooser entry point
  In order to get AI guidance while building a course
  As a teacher
  I need a "Help, what should I do?" option in the activity chooser

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | enabled           | 1 | tool_sherpa |
      | enablehelpchooser | 1 | tool_sherpa |

  Scenario: The Sherpa help chooser opens from the activity chooser button
    Given I am on the "Course 1" course page logged in as teacher1
    And I turn editing mode on
    When I click on "Add content" "button" in the "General" "section"
    And I click on "Help, what should I do?" "link"
    Then I should see "What would you like to do?"
    And I should see "Describe in your own words"
    And I should see "Assignment"

  Scenario: The help chooser entry is hidden when the feature is disabled
    Given the following config values are set as admin:
      | enablehelpchooser | 0 | tool_sherpa |
    And I am on the "Course 1" course page logged in as teacher1
    And I turn editing mode on
    When I click on "Add content" "button" in the "General" "section"
    Then I should not see "Help, what should I do?"
