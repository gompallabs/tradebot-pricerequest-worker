Feature: I receive an ApiRequestEvent and process it with the DataProcessor
  We use the payload of "api.json" file

  Scenario:
    Given an ApiRequestEvent event is received on "processing" queue
    Then I can transform it with the data processor to an OHLCV series

  Scenario:
    Given an ApiRequestEvent event is received on "processing" queue
    And I handle the event with the ApiRequestEventHandler
    # this will change to persist data to datastore
    Then the handler should return an OHLCV series
    And the tickData should contain 31 PriceOhlcv objects
    And the PriceOhlcv object nb "0" should have the following properties:
    """
    {
      "tsms": 1695238461000,
      "open": 26879.8,
      "high": 26879.9,
      "low": 26879.8,
      "close": 26879.9,
      "buyVolume": 2.993,
      "sellVolume": 8.358
    }
    """
    And the PriceOhlcv object nb "30" should have the following properties:
    """
    {
      "tsms": 1695238491000,
      "open": 26899.8,
      "high": 26899.8,
      "low": 26899.8,
      "close": 26899.8,
      "buyVolume": 0.084,
      "sellVolume": 0.0
    }
    """
