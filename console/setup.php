<?php
function autoloaderPhp($class){
  require("./" . $class . ".php");
}
spl_autoload_register("autoloaderPhp");
DAOConstants::$pdo = new PDO("pgsql:host=" . DAOConstants::host . "; port=5432; dbname=" . DAOConstants::dbname, DAOConstants::user, DAOConstants::password);

function createDatabase(){
  try{
    DAOConstants::$pdo ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    DAOConstants::$pdo->exec("drop table if exists stockMarket;");
    DAOConstants::$pdo->exec("Create table stockMarket(
      symbol varchar(10),
      name varchar(1000),
      sector varchar(1000),
      industry varchar(500),
      market_id int generated always as identity primary key
    );");
  }catch(PDOException $e){
      echo $e->getMessage();
      exit;
  }
  return DAOConstants::$pdo;
}

  function readCvs(){
  $file = fopen("companylist.csv", "r");
  while (!feof($file)){
    $reader[] = fgetcsv($file);
  }
    fclose($file);
    return $reader;
  }

  function addToDatabase($input){
    if(gettype($input) != "array"){
      exit;
    }
    foreach($input as $row){
      try{
    $stmt = DAOConstants::$pdo->prepare("Insert into stockMarket (symbol, name, sector, industry) values (:symbol, :name, :sector, :industry)");
    $stmt-> bindParam(':symbol', $row[0]);
    $stmt-> bindParam(':name', $row[1]);
    $stmt-> bindParam(':sector', $row[2]);
    $stmt-> bindParam(':industry', $row[3]);
    $stmt->execute();
  }catch(PDOException $e){
      echo $e->getMessage();
      exit;
    }
 }
}

  function setup(){
    createDatabase();
    addToDatabase(readCvs());
  }
  setup(DAOConstants::$pdo);

?>
