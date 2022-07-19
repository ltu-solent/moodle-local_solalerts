@local @local_solalerts @sol @javascript
Feature: View SolAlerts
    As various roles I should see alerts
    In order to be aware of relevant information
    I need to for specified contexts

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
        And the following "courses" exist:
        | fullname | shortname |
        | Course 1 | C1        |
        And the following "activities" exist:
        | activity   | name                   | intro                         | course | idnumber    |
        | assign     | Test assignment name   | Test assignment description   | C1     | assign1     |
        | page       | Test page name         | Test page description         | C1     | page1       |
        And the following "users" exist:
        | username | firstname | lastname | email |
        | student1 | Student | 1 | student1@example.com |
        | teacher1 | Teacher | 1 | teacher1@example.com |
        | ee1 | External | Examiner1 | ee1@example.com |
        And the following "roles" exist:
        | shortname | name  | archetype |
        | ee     | External Examiner | editingteacher |
        And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
        | ee1      | C1     | ee             |
        And the following solalert alert exists:
        | title             | Page view for all |
        | content           | All users are viewing a page |
        | contenttype       | alert |
        | alerttype         | success |
        | pagetype          | page-mod-page-view |
        | enabled           | 1 |
        And the following solalert alert exists:
        | title             | Page view for students |
        | content           | Students are viewing a page |
        | alerttype         | warning |
        | pagetype          | page-mod-page-view |
        | rolesincourse     | student |
        | enabled           | 1 |
        And the following solalert alert exists:
        | title             | Page view for teachers |
        | content           | Teachers are viewing a page |
        | alerttype         | warning |
        | pagetype          | page-mod-page-view |
        | rolesincourse     | editingteacher |
        | enabled           | 1 |
        And the following solalert alert exists:
        | title             | Assign view for External Examiners |
        | content           | External Examiners are viewing an assignment |
        | alerttype         | info |
        | pagetype          | page-mod-assign-view |
        | rolesincourse     | ee |
        | enabled           | 1 |
        And the following solalert alert exists:
        | title             | Dashboard view for All |
        | content           | All users are viewing the dashboard |
        | alerttype         | info |
        | pagetype          | page-my-index |
        | enabled           | 1 |
        And the following solalert alert exists:
        | title             | No-one can see this |
        | content           | No-one is viewing this alert |
        | alerttype         | info |
        | pagetype          | page-my-index |
        | enabled           | 0 |
    Scenario: View Alerts given various contexts
        # Student
        Given I log in as "student1"
        And I am on "Course 1" course homepage
        And I click on "Test page name" "link"
        Then I should see "All users are viewing a page"
        And I should see "Students are viewing a page"
        And I should not see "Teachers are viewing a page"
        When I am on "Course 1" course homepage
        And I click on "Test assignment name" "link"
        Then I should not see "External Examiners are viewing an assignment"
        When I follow "Dashboard" in the user menu
        Then I should see "All users are viewing the dashboard"
        And I should not see "No-one is viewing this alert"
        # Teacher
        When I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I click on "Test page name" "link"
        Then I should see "All users are viewing a page"
        And I should not see "Students are viewing a page"
        And I should see "Teachers are viewing a page"
        When I am on "Course 1" course homepage
        And I click on "Test assignment name" "link"
        Then I should not see "External Examiners are viewing an assignment"
        When I follow "Dashboard" in the user menu
        Then I should see "All users are viewing the dashboard"
        And I should not see "No-one is viewing this alert"
        # External Examiner
        When I log in as "ee1"
        And I am on "Course 1" course homepage
        And I click on "Test page name" "link"
        Then I should see "All users are viewing a page"
        And I should not see "Students are viewing a page"
        And I should not see "Teachers are viewing a page"
        When I am on "Course 1" course homepage
        And I click on "Test assignment name" "link"
        Then I should see "External Examiners are viewing an assignment"
        When I follow "Dashboard" in the user menu
        Then I should see "All users are viewing the dashboard"
        And I should not see "No-one is viewing this alert"