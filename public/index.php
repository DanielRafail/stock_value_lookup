<!DOCTYPE>
<html>
<head>
  <meta charset="UTF-8">
  <Title>Stock Market information</Title>
  <link rel="stylesheet" href="index_stylesheet.css">
</head>
<body>
<div id="main_body">
  <header id="header">
    <h1 id="page_title">Stock Market Information</h1>
  </header>
  <nav>
    <input type="button" value="Home"/>
    <input type="button" value="About"/>
  </nav>
  <div id="description">
    <p></p>
  </div>
  <p id="prompt">Please chose the company you wish to examine:</p>
  <Form action="" method = "POST">

  <?php
    /**
    *
    */
    function autoloaderPhp($class){
      require("./" . $class . ".php");
    }
    spl_autoload_register("autoloaderPhp");
    // Create a connection to the database
    DAOConstants::$pdo = new PDO("pgsql:host=" . DAOConstants::host
      . "; port=5432; dbname=" . DAOConstants::dbname,
    DAOConstants::user, DAOConstants::password);

    /**
    * Function appends a select tag to the page and
    * adds all records from the Stock Market database as the options
    *
    */
    function fillSelectWithSymbols(){
      try{
        // Query against the database to retrieve all symbols
        $sql = "select * from stockMarket";
        echo '<select name="symbols" id="symbollist">';
        foreach (DAOConstants::$pdo->query($sql) as $row) {
          if ($row['symbol'] === "Symbol") {
            // Outputs the first option as blank
            echo '<option value=""> </option>';
          } else {
            // Append symbols as options in the select tag along with the company
            // name
            echo '<option value=' . $row['symbol'] .
              ' name="symbol">' . $row['symbol'] .
              ' - ' . $row['name'] . '</option>';
          }
        }
        echo '</select>';
        echo '<br><input type="submit" value="Submit" name="submit" id="submit">';
      } catch(PDOException $e){
          echo $e->getMessage();
          exit;
      }
    }

    /**
    * This function queries the AlphaVantage API for a company's stock Information
    *
    */
    function retrieveDataFromAlphavantage() {
      // Variables needed to created a url to use the alphavantage api
      $requestTypeURL = "function=TIME_SERIES_INTRADAY";
      $apiKey = 'F65M552NK586I4QJ';

      if (isset($_POST['submit']) && !empty($_POST['symbols'])) {
        $symbol = $_POST['symbols'];
        $url = 'https://www.alphavantage.co/query?'. $requestTypeURL . '&symbol='
          . $symbol . '&interval=1min&outputsize=compact&apikey=' . $apiKey;

        // Retrieving data from the url in JSON format
        // Returns false if it fails
        $content = @file_get_contents($url);

        if(!$content){
          throw new Exception('Unable to retrieve ticker stock info (invalid ticker symbol)');
        }

        $jsonData = json_decode($content, true);

        // Saves only the first element of the retrieved data
        $metadata = current($jsonData);

        // Append data
        echo '<div id="lasttrade">';
        echo '<h2>' . $metadata['2. Symbol'] . '</h2>';
        echo '<p>Time Zone: ' . $metadata['6. Time Zone'] . '</p>';
        echo '<p>Last Refreshed: ' . $metadata['3. Last Refreshed'] . '</p>';

        // Validation to make sure 'Time Series (1min)' exists
        if(isset($jsonData['Time Series (1min)'])){
          $lastTrade = current($jsonData['Time Series (1min)']);
          echo '<p>Last trade closing value: ' . $lastTrade['4. close'] . '</p>';
        }

        echo '</div>';
      } else {
        // if submit and smbols aren't set
        throw new Exception('Ticker symbol not specified.');
      }
    }

    /**
    * This function queries the YAHOO finance API for 5 set of
    * the company's latest news
    */
    function retrieveDataFromYahoo() {
      if (isset($_POST['submit'])  && !empty($_POST['symbols'])) {
        $symbol = $_POST['symbols'];
        $url = 'http://finance.yahoo.com/rss/headline?s=' . $symbol;

        // Retrieve data from a url in XML format
        $results = @file_get_contents($url);

        if(!$results){
          throw new Exception('Unable to retrieve ticker stock info (invalid ticker symbol)');
        }

        $xml = new \DOMDocument();
        @$xml->loadXML($results);

        // Retrieving nodes containing the title, the publication date,
        // and the description of a piece of news

        $titles = $xml->getElementsByTagName('title');
        $pubDates = $xml->getELementsByTagName('pubDate');
        $descriptions = $xml->getElementsByTagName('description');

        // Append the latest 5 news item
        echo "<div id=\"articles\">";
        for ($i = 0; $i < 6; $i++) {
          if(!isset($pubDates->item($i)->nodeValue)){
            break;
          } else {
            echo '<p>' . $pubDates->item($i)->nodeValue . '</p>';
            echo '<h3>' . $titles->item($i)->nodeValue . '</h3>';
            echo '<p>' . $descriptions->item($i)->nodeValue . '</p>';
            echo '<hr />';
          }
        }
        echo "</div>";
      } else {
        // if submit and smbols aren't set
        throw new Exception('Ticker symbol not specified.');
      }
    }

    fillSelectWithSymbols();

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
      try {
        retrieveDataFromAlphavantage();
        retrieveDataFromYahoo();
      } catch (Exception $e) {
        echo "<p id=\"error\">".$e->getMessage()."</p>";
      }

    }

  ?>
  </Form>
</div>
<footer>
  <p>copyright info</p>
</footer>
</body>
</html>
