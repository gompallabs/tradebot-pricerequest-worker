Feature: I receive a FileDownloadedEvent and process it with the DataProcessor

  Scenario:
    Given a FileDownloadedEvent event is received on "processing" queue
    Then I copy the file with a ".backup" extension
    And I decompress the file
    And I restore the backup file to the original file
    And I parse the csv file
    And I can transform it with the data processor to an OHLCV series
