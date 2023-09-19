Feature: I receive an ApiRequestEvent and process it with the DataProcessor

  Scenario:
    Given an ApiRequestEvent event is received on "processing" queue
    Then the event should contain a Coin data array
    And the event should contain a Source data array
    And the event should contain a data array

  Scenario:
    Given an ApiRequestEvent event is received on "processing" queue
    Then I can transform it with the data processor to an OHLCV series