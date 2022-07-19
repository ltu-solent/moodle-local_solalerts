@local @local_solalerts @sol @javascript
Feature: Manage SolAlerts
  As the site administrator
  In order to alert targetted users of relevant information
  I need to manage alerts for specified contexts

  Background:
    Given the following config values are set as admin:
    | config | value    |
    | theme  | solent   |
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > SolAlerts" in site administration
    And I set the field "Pagetypes" to multiline:
    """
    page-my-index
    page-frontpage
    page-mod-assign-view
    page-mod-page-view
    """
    And I press "Save changes"

  Scenario: Manage an Alert
    Given I log in as "admin"
    And I navigate to "Appearance > Sol Alerts" in site administration
    And I click on "Create new alert" "link"
    Then I should see "Create new alert"
    When I set the following fields to these values:
    | Title | Page view for all |
    | Alert content | All users are viewing a page |
    | Alert type | Information |
    | Pagetype | Mod Page View |
    | Enabled | 1 |
    And I press "Save changes"
    Then I should see "Pagetype: Mod Page View" in the "Page view for all" "table_row"
    And I click on "Edit" "link" in the "Page view for all" "table_row"
    And I should see "Edit SolAlert"
    When I set the following fields to these values:
        | Pagetype | Mod Assign View |
    And I press "Save changes"
    Then I should see "Pagetype: Mod Assign View" in the "Page view for all" "table_row"
    And I click on "Delete" "link" in the "Page view for all" "table_row"
    And I should see "Confirm deletion of \"Page view for all\""
    And I press "Delete"
    Then I should see "\"Page view for all\" has been deleted."
