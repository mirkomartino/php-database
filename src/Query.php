<?php
namespace Core;
/**
* Classe per la gestione semplificata delle query
* @author diguglielmo
*
*/
class Query
{
public	$dbType;
private $sqlLines;
private $risultato;

public 	$conn;
public	$sql;
public 	$row;

public 	$ErrorID;
public 	$ErrorDS;

public function __construct($pDbType = 'MYSQL') {
$this->dbType = $pDbType;
$this->sql = "";
$this->ErrorID = 0;
$this->ErrorDS = '';
}

/**
* Apro la query di tipo select
* @return false in caso di errore sulla query
*/
public function Open(){
    if($this->dbType=="MYSQLI") {
        $this->risultato = mysqli_query(Database::$conn, $this->sql);
        if ($this->risultato) {
            $this->next();
            return true;
        } else {
            $this->row = false;
            $this->ErrorID = mysqli_errno(Database::$conn);
            $this->ErrorDS = mysqli_error(Database::$conn);
            return false;
        }
    }

}

/**
* funzione per eseguire query di tipo Insert o update
* @return Ritorna true se tutto è andato bene
*/
public function Exec(){
if($this->dbType=="MYSQLI") {
//echo $this->sql;
$this->risultato = mysqli_query(Database::$conn,$this->sql);
if ($this->risultato) {
return(true);
} else {
$this->row = false;
$this->ErrorID = mysqli_errno(Database::$conn);
$this->ErrorDS = mysqli_error(Database::$conn);
return(false);
}
}



}

/**
* Ultima ID inserita se autoincrementale (per sessione restituita da mysql)
*
*/
public function DsGetLastID(){
    if($this->dbType=="MYSQLI") {
        return mysqli_insert_id(Database::$conn);
    }
}

/**
* Restituisce true in caso ci si trovi alla fine della tabella
*
*/
public function eof(){
    if($this->dbType=="MYSQLI") {
        return( !$this->row );
    }
}

/**
* Posiziona l'oggetto row alla riga successiva
*
*/
public function Next(){
    if($this->dbType=="MYSQLI") {
        $this->row = @mysqli_fetch_assoc($this->risultato);
    }
}

/**
* serve per liberare la memoria dai risultati. da utilizzare solo se si sa che la query tira fuori una bella mole di dati fuori
si libera la memoria per velocizzare le query successive eventuali. se i risultati
sono pochi inutile utilizzarlo anzi lanciamo un comando in piu che non ci serve
*
*/
public function Free_Result(){
    if($this->dbType=="MYSQLI") {
        return( mysqli_free_result($this->risultato) );
    }
}

/**
Numero di risultati che restituisce la query
*
*/
public function Num_Rows(){
    if($this->dbType=="MYSQLI") {
        return( mysqli_num_rows($this->risultato) );
    }
}
/**
* Carica la query da un file, se viene passato l'array CampiNecessari
* la query viene filtrata restituendo solo le rige necessarie
*
* @param string $pNomeFile Nome del file contenente la query in formato SVISOFT
* @param array $pCampiNecessari (Facoltativo) Array con i campi obbligatori
*/
public function LoadFromFile($pNomeFile, $pCampiNecessari = array() ){
    $this->sqlLines = file($pNomeFile);
    $this->GeneraSQL($pCampiNecessari);
}

/**
* Carica la query da un file, se viene passato l'array CampiNecessari
* la query viene filtrata restituendo solo le rige necessarie
*
* @param array $pArray Array di stringhe con la query sql in formato SVISOFT
* @param array $pCampiNecessari (Facoltativo) Array con i campi obbligatori
*/
public function LoadFromArray($pArray, $pCampiNecessari = array() ){
    $this->sqlLines = $pArray;
    $this->GeneraSQL($pCampiNecessari);
}

public function LoadFromText($pText, $pCampiNecessari = array() ){
    $pArray = explode("\r\n",$pText);
    //printr($pArray);exit;
    $this->sqlLines = $pArray;
    $this->GeneraSQL($pCampiNecessari);
    //printr( $this->sql );
}
/**
* Metodo privato utilizzato per analizzare la query in formato svisoft
* @param array $pCampiNecessari Array con i campi obbligatori
*/
private function GeneraSQL($pCampiNecessari){
    $this->sql = "";
    foreach ($this->sqlLines as $rigasql) {
        // Ma sta riga mi serve ?
        $riga = explode("|", $rigasql);
        if(Count($riga) == 1) {
        // La riga non ha il carattere separatore |, Mi serve direttamente tutta la riga
        $this->sql .= " ". $rigasql;
        } else {
        if ($riga[0]=="") {
        // Non c'è un campo, visualizzo la riga, Questa riga serve sicuramente
        $this->sql .= " ". $riga[1];
        } else {
        // Il campo c'è ed è in $riga[0] la query in $riga[1]
        if( $this->CampoServe($riga[0], $pCampiNecessari) ) {
        // Questa riga serve, visualizzo l'SQL
        //$this->sql .= " ". strtolower($riga[1]); // DDG 2015/09/16
        $this->sql .= " ". $riga[1];
        }
        }
        }
    }
}

// Questa funzione verifica se il campo è presente tra quelli passati
function CampoServe($pCampo, $pCampiPassati){
    // Controllo se il campo $pCampo è presente tra i campi passati
    $trovato=false;
    foreach ($pCampiPassati as $campo) {
        //echo "#".$campo."# == ".$pCampo.'<hr>';
        if( "#".strtolower($campo)."#" == strtolower($pCampo)) {
        // Uamino, l'ho trovato
        $trovato = true;
        } else {
        // NO
        }
    }

    return $trovato;
}

// Parametro Testo
public function parTxt($pNome, $pValore){
$this->sql = str_ireplace("#". $pNome ."#", $pValore, $this->sql);
}

// Parametro Stringa
public function parStr($pNome, $pValore){
if($this->dbType=='MSSQL'){
$pValore = utf8_decode($pValore);
$pValore = str_replace("'", "''", $pValore);
$this->sql = str_ireplace("#". $pNome ."#", "'". $pValore ."'", $this->sql);
}else{
if ($pValore==''){
$this->sql = str_ireplace("#". $pNome ."#", "NULL", $this->sql);
}else{
$this->sql = str_ireplace("#". $pNome ."#", "'". addslashes($pValore) ."'", $this->sql);
}
}
}

// Parametro Numero intero
public function parInt($pNome, $pValore){
$pValore = $pValore=='' ? 0 : $pValore;
$this->sql = str_ireplace("#". $pNome ."#", $pValore, $this->sql);
}

// Parametro Numero con la virgola
public function parFloat($pNome, $pValore){
$pValore = $pValore=='' ? 0 : $pValore;
$pValore = str_replace(",",".",$pValore);
$this->sql = str_ireplace("#". $pNome ."#", $pValore, $this->sql);
}

// Parametro Data
public function parDate($pNome, $pValore){
if($this->dbType == "MSSQL") {
$this->sql = str_ireplace("#". $pNome ."#", "'". $pValore ."'", $this->sql);
}else{
if ($pValore==''){
$this->sql = str_ireplace("#". $pNome ."#", "NULL", $this->sql);
}else{
$this->sql = str_ireplace("#". $pNome ."#", "'". $pValore ."'", $this->sql);
}
}
}

//Parametro Ora
public function parTime($pNome, $pValore){
if($this->dbType == "MSSQL") {
$this->sql = str_ireplace("#". $pNome ."#", "'". $pValore ."'", $this->sql);
}else{
if ($pValore==''){
$this->sql = str_ireplace("#". $pNome ."#", "NULL", $this->sql);
}else{
$this->sql = str_ireplace("#". $pNome ."#", "'". $pValore ."'", $this->sql);
}
}
}

// Parametro Data e Ora
public function parDateTime($pNome, $pValore){
if($this->dbType == "MSSQL") {
$this->sql = str_ireplace("#". $pNome ."#", "'". $pValore ."'", $this->sql);
}else{
if ($pValore==''){
$this->sql = str_ireplace("#". $pNome ."#", "NULL", $this->sql);
}else{
$this->sql = str_ireplace("#". $pNome ."#", "'". $pValore ."'", $this->sql);
}

}
}

}
?>