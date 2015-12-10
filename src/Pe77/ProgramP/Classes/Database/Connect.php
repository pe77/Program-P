<?php

namespace Pe77\ProgramP\Classes\Database;

/**
 * Classe de conexão com o DB Mysql
 * @author pe77
 *
 */
class Connect
{
	
	/**
	 * Onde esta o banco
	 * @var string
	 */
	private static $host = '';
	
	/**
	 * usuario de acesso
	 * @var string
	 */
	private static $user = '';
	
	/**
	 * senha do banco para o usuário $user
	 * @var string
	 */
	private static $pass = '';
	
	/**
	 * Nome da Database
	 * @var string
	 */
	private static $dbName = '';
	
	/**
	 * Verifica se conexão com o banco está aberta
	 * @var bool
	 */
	private static $isConnected = false;
	
	
	/**
	 * Identificador da ultima conexão aberta
	 * @var int|bool
	 */
	protected static $connIdent;
	
	protected static $transactionOpen = false;
	
	
	/**
	 * Inicia classe com as configurações iniciais do banco
	 * @param array $config
	 */
	public static function init($config)
	{
		// "carrega" configuração
		self::$host = $config['host'];
		self::$user = $config['user'];
		self::$pass = $config['pass'];
		self::$dbName = $config['dbName'];
		
	}
	
	
	/**
	 * Abre Conexão com o Banco
	 */
	private static function OpenConnect()
	{
		if (self::$dbName == '' || self::$host == '' || self::$user == '') 
			trigger_error("Classe [Connect] não iniciada [init]. Beijo do Gordo, WOW!");
		//
		
        // tenta abrir uma conexão
        self::$connIdent = mysql_connect(self::$host, self::$user, self::$pass) or die('Erro ao conectar com a base de dados. Por que? - Você deve estar se perguntando : ' . mysql_error());
		mysql_select_db(self::$dbName, self::$connIdent) or die('Base de dados não encontrada. Luke i am your father : ' . mysql_error());
        
		// seta a conexão para aberta
		self::$isConnected = true;
	}
	
	public static function CreateDatabase($database) 
	{
		self::$connIdent = mysql_connect(self::$host, self::$user, self::$pass);
		$sql = '
				CREATE DATABASE 
					IF NOT EXISTS
				' . $database . ';
			';
		
		mysql_query($sql, self::$connIdent) or die('Query inválida: ' . mysql_errno() . "\nEssequeéli:\n" . $sql);
	}
	
	/**
	 * Verifica se a base de dados existe
	 * @param String $database
	 * @return boolean
	 */
	public static function CheckDatabaseExist($database) 
	{
		self::$connIdent = mysql_connect(self::$host, self::$user, self::$pass);
		
		$sql = 'SELECT COUNT(*) AS `exists` FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMATA.SCHEMA_NAME="' . $database . '"';
		
		$result = mysql_query($sql, self::$connIdent) or die('Query inválida: ' . mysql_errno() . "\nEssequeéli:\n" . $sql);
		
		$data = mysql_fetch_assoc($result);
		return $data['exists'] == '1';
	}
	

	/**
	 * Executa o comando mysql e retorna o valor
	 * @param string $sql
	 * @return conteudo da query
	 */
	public static function Query($sql)
	{
		// verifica se a conexão já esta aberta, se não tiver abre
		if(!self::$isConnected)
			self::OpenConnect();
		//
		
		$result = mysql_query($sql, self::$connIdent) or die('Query inválida: ' . mysql_error() . "\nEssequeéli:\n" . $sql);
		return $result;
	}
	
	/**
	 * Semelhante a Função 'Query', só que já retorna o resultado em um array
	 * @param string $sql
	 * @return array com os resultados da procura
	 */
	public static function Fetch($sql)
	{
		$data = array();
		$result = self::Query($sql);
		
		while ($row = mysql_fetch_assoc($result))
			$data[] = $row;
		//
		
		return $data;
	}
	
	/**
	 * Fecha a conexão com o banco
	 */
	public static function Close() 
	{
		mysql_close(self::$connIdent);
	}
	
	/**
	 * Abre uma transação com o banco, se já houver uma em aberta, "comita"
	 */
	public static function Begin()
	{
		if(!self::$transactionOpen)
			self::Commit();	
		
		self::Query("begin");
		self::$transactionOpen = true;
	}
	
	
	/**
	 * Envia querys ao banco e realiza ( meu bem )
	 */
	public static function Commit()
	{
		self::Query("commit");
		self::$transactionOpen = false;
	}
	
	/**
	 * Retorna a transação
	 */
	public static function Rollback()
	{
		self::Query("rollback");
		self::$transactionOpen = false;
	}
	
	/**
	 * Quase a mesma coisa do fetch mas só retorna 1, ONE saca? 
	 * @param string $sql
	 * @param int $index indice que quer retornar 0 default
	 * @return mysql row string|int
	 */
	public static function GetOne($sql, $index = 0) {
		$data = self::Fetch($sql);
		if(count($data) > 0)
			return $data[$index];
		else 
			return false;
	}
}