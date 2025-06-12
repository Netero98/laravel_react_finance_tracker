Feature: Wallets dashboard block

#  Scenario: Add wallet and see it
#    Given I am a user
#    When I open "/" page
#    Then I see "Wallets"
#    Then I see "Wallet name"
#    Then I see "Initial balance"
#    Then I see "Currency"
#    Then I click "button_add_wallet" element
#    Then I fill "wallet_name_input" field with "Cucumber wallet name 1"
#    Then I fill "wallet_currency_input" field with "wrong currency"
#    Then I fill "wallet_initial_balance_input" field with "95534"
#    Then I click "save-wallet-button" element
#    Then I see "Invalid currency"
#    Then I fill "wallet_currency_input" field with "USD"
#    Then I click "save-wallet-button" element
##    to be sure that its not the form data
#    Then I go to "/" page
#    Then I see "Cucumber wallet name 1"
#    Then I see "95534"

  Scenario: Add category and see it
    Given I am a user
    When I open "/" page
    Then I see "Categories"
    Then I see "Category name"
    Then I click "button_add_category" element
    Then I see "Add New Category"
    Then I fill "category_name_input" field with "Cucumber category name 1"
    Then I click "save-category-button" element
    # to be sure that it's not the form data
    Then I go to "/" page
    Then I see "Cucumber category name 1"

#  Scenario: Add transaction and check wallet balance
#    Given I am a user
#    When I open "/" page
#    Then I see "Transactions"
#    # Check initial wallet balance
#    Then I see "199898"
#    Then I see "202898"
#    Then I click "button_add_transaction" element
#    Then I see "Add New Transaction"
#    Then I click "transaction_wallet_id_input" element
#    # wallet rub
#    Then I select element with data-value = "a62a368e-cf9a-4363-b5f5-408049e05e58"
#    Then I wait a little
#    Then I click "transaction_category_id_input" element
#    Then I select element with data-value = "b9ef1208-eea1-408c-96d3-c24b8825f193"
#    Then I fill "transaction_amount" field with "1845"
#    Then I fill "transaction_description" field with "Cucumber test add transaction"
#    Then I click "save-transaction-button" element
#    Then I see "204743"
#    Then I see "1,845"
#    Then I see "Cucumber test add transaction"
#    Then I see "204743"
#    Then I see "8,779,883 RUB"
#    Then I see "98,374.039 USD"
