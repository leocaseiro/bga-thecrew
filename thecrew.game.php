<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * thecrew implementation : © Nicolas Gocel <nicolas.gocel@gmail.com> & Timothée Pecatte <tim.pecatte@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * thecrew.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

$swdNamespaceAutoload = function ($class)
{
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'CREW') {
    array_shift($classParts);
    $file = dirname(__FILE__) . "/modules/php/" . implode(DIRECTORY_SEPARATOR, $classParts) . ".php";
    if (file_exists($file)) {
      require_once($file);
    } else {
      var_dump("Impossible to load thecrew class : $class");
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


use CREW\Game\Globals;

class thecrew extends Table
{
  use CREW\States\MissionTrait;
  use CREW\States\PickTaskTrait;
  use CREW\States\TrickTrait;
  use CREW\States\CommunicationTrait;
  use CREW\States\DistressTrait;
  use CREW\States\QuestionTrait;
  use CREW\States\MoveTileTrait;
  use CREW\States\GiveTaskTrait;
  use CREW\States\RestartMissionTrait;


  public static $instance = null;
  public function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    Globals::declare($this);
  }

  public static function get()
  {
    return self::$instance;
  }

  protected function getGameName()
  {
      return "thecrew";
  }



  /*
   * setupNewGame:
   *  This method is called only once, when a new game is launched.
   * params:
   *  - array $players
   *  - mixed $options
   */
  protected function setupNewGame($players, $options = [])
  {
    CREW\Game\Globals::setupNewGame();
    CREW\Game\Players::setupNewGame($players);
    CREW\Game\Stats::setupNewGame();
    CREW\Cards::setupNewGame($players, $options);

    if($options[OPTION_MISSION] == CAMPAIGN)
      CREW\LogBook::loadCampaign();
    else {
      $mission = $options[OPTION_MISSION] == NEW_CAMPAIGN? 1 : $options[OPTION_MISSION];
      CREW\LogBook::startMission($mission);
    }

    $this->activeNextPlayer();
  }


  /*
   * getAllDatas:
   *  Gather all informations about current game situation (visible by the current player).
   *  The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
   */
  protected function getAllDatas()
  {
    $pId = self::getCurrentPId();
    $ids = CREW\Game\Players::getAll()->getIds();
    $pId2 = in_array($pId, $ids) ? $pId : $ids[0];
    $status = CREW\LogBook::getStatus();
    return [
      'players' => CREW\Game\Players::getUiData($pId),
      'playersOrder' => CREW\Game\Players::getTurnOrder($pId2, true),
      'missions' => CREW\Missions::getUiData(),
      'status' => $status,
      'commanderId' => Globals::getCommander(),
      'specialId' => Globals::getSpecial(),
      'specialId2' => Globals::getSpecial2(),
      'showIntro' => $status['mId'] == 1 && $status['total'] == 1 && Globals::isCampaign() && Globals::getTrickCount() <= 1,
      'trickCount' => Globals::getTrickCount(),
      'isCampaign' => Globals::isCampaign(),

      'isVisibleDiscard' => Globals::isVisibleDiscard(),
      'discard' => Globals::isVisibleDiscard()? CREW\Cards::getInLocation('trick%')->toArray() : [],
    ];
  }


  /*
   * getGameProgression:
   *  Compute and return the current game progression approximation
   *  This method is called each time we are in a game state with the "updateGameProgression" property set to true
   */
  public function getGameProgression()
  {
    $nbTotalCards = Globals::isChallenge()? 30 : 40;
    $nbPlayedCards = $nbTotalCards - CREW\Cards::countRemeaning();

    return 100 * $nbPlayedCards / $nbTotalCards;
  }



  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayer)
  {
    // Only one player active => try to zombiepass transition
    if ($state['type'] === "activeplayer") {
      // TODO
      /*
        self::setGameStateValue( 'mission_finished', -1 );
        self::setGameStateValue( 'end_game',1);
        $this->gamestate->nextState( "zombiePass" );
        break;
      */

      if (array_key_exists('zombiePass', $state['transitions'])) {
        $this->gamestate->nextState('zombiePass');
        return;
      }
    }
    // Multiactive => make player non-active
    else if ($state['type'] === "multipleactiveplayer") {
      $this->gamestate->setPlayerNonMultiactive($activePlayer, '');
      return;
    }

    throw new BgaVisibleSystemException('Zombie player ' . $activePlayer . ' stuck in unexpected state ' . $state['name']);
  }


  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
    if( $from_version <= 2010221013){
      self::testBigMerge();
    }

    if($from_version <= 2103171142){
      self::applyDbUpgradeToAllDB("DELETE FROM DBPREFIX_card WHERE `color` = 6");
    }

    if($from_version <= 2103172308){
      try {
        self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD `distress_auto` smallint(1) DEFAULT 0 COMMENT 'none, autono, autoyes'");
      } catch(Exception $e){
          print_r($e);
      }
    }

    if( $from_version <= 2204250137){
      try {
        self::applyDbUpgradeToAllDB("CREATE TABLE IF NOT EXISTS `global_variables` (`name` varchar(255) NOT NULL, `value` JSON, PRIMARY KEY (`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
      } catch(Exception $e){
          print_r($e);
      }
    }


    // Doing all the necessary previous upgrade
    if($from_version <= 2103181046){
      $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `player` LIKE 'comm_card_id'");
      if(is_null($result)){
        self::testBigMerge();
      }

      self::applyDbUpgradeToAllDB("DELETE FROM DBPREFIX_card WHERE `color` = 6");

      $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `player` LIKE 'distress_auto'");
      if(is_null($result)){
        self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD `distress_auto` smallint(1) DEFAULT 0 COMMENT 'none, autono, autoyes'");
      }
    }


    if($from_version <= 2103202326){
      try {
        self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD `continue_auto` smallint(1) DEFAULT 0 COMMENT 'none, autoyes'");
        self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD `preselect_card_id` int(10) unsigned NULL COMMENT 'id of the preselected card'");
      } catch(Exception $e){
          print_r($e);
      }
    }

    // restart mission answers
    if( $from_version <= 2205100108){
      try {
        self::applyDbUpgradeToAllDB("ALTER TABLE `DBPREFIX_player` ADD `restart_mission_answer` smallint(1) DEFAULT 0 COMMENT 'none, dontuse, agree'");
      } catch(Exception $e){
          print_r($e);
      }
    }
  }


  public function testBigMerge()
  {
  try{
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player CHANGE COLUMN `card_id` `distress_card_id` INT");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD `comm_card_id` int(10) DEFAULT NULL COMMENT 'id of the communicated card'");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD `distress_choice` smallint(1) DEFAULT 0 COMMENT 'unset, clockwise, anticlockwise or dontuse'");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD `reply_choice` int(10) unsigned NULL COMMENT 'id of the chosen reply'");

    // ! important ! Use DBPREFIX_<table_name> for all tables
    $commCards = self::getObjectListFromDB("SELECT * FROM card WHERE card_location = 'comm'");
    foreach($commCards as $card){
      if($card['card_type'] != 6){
        self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_player SET `comm_card_id` = ". $card['card_id'] ." WHERE `player_id` = ". $card['card_location_arg']);
        self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_card SET `card_location` = 'hand' WHERE `card_id` = ". $card['card_id']);
      }
      else {
        self::DbQuery("DELETE FROM card WHERE `card_id` = ". $card['card_id']);
      }
    }


    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_task CHANGE COLUMN `card_type` `color` INT");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_task CHANGE COLUMN `card_type_arg` `value` INT");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_task CHANGE COLUMN `token` `tile` VARCHAR(3)");

    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_card CHANGE COLUMN `card_type` `color` INT");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_card CHANGE COLUMN `card_type_arg` `value` INT");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_card CHANGE COLUMN `card_location` `card_location` VARCHAR(50)");
    self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_card ADD `card_state` int(11) NOT NULL");
    self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_card SET card_location = 'table' WHERE `card_location` = 'cardsontable'");
    self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_card SET card_location = CONCAT(`card_location`, '_', `card_location_arg`) WHERE `card_location_arg` != ''");

  } catch(Exception $e){}
  }



  ///////////////////////////////////////////////////////////
  // Exposing proteced method, please use at your own risk //
  ///////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId(){
    return self::getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text){
    return self::_($text);
  }

  ////////////////////////////////////////////////////////////////
  // Debug: Load bug report state into this table save slot #1 //
  ////////////////////////////////////////////////////////////////
   /*
   * loadBug: in studio, type loadBug(20762) into the table chat to load a bug report from production
   * client side JavaScript will fetch each URL below in sequence, then refresh the page
   */
  public function loadBug($reportId)
  {
    if ($this->getBgaEnvironment() != 'studio') {
      throw new Exception("Only available at Studio");
    }

    $db = explode('_', self::getUniqueValueFromDB("SELECT SUBSTRING_INDEX(DATABASE(), '_', -2)"));
    $game = $db[0];
    $tableId = $db[1];
    // self::notifyAllPlayers('loadBug1', "https://studio.boardgamearena.com/admin/studio/getSavedGameStateFromProduction.html?game=$game&report_id=$reportId&table_id=$tableId", []);
    // self::notifyAllPlayers('loadBug2', "https://studio.boardgamearena.com/table/table/loadSaveState.html?table=$tableId&state=1", []);
    // self::notifyAllPlayers('loadBug3', "https://studio.boardgamearena.com/1/$game/$game/loadBugSQL.html?table=$tableId&report_id=$reportId", []);
    // self::notifyAllPlayers('loadBug4', "https://studio.boardgamearena.com/admin/studio/clearGameserverPhpCache.html?game=$game", []);

    self::notifyAllPlayers('loadBug', "Trying to load <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a>", [
      'urls' => [
        // Emulates "load bug report" in control panel
        "https://studio.boardgamearena.com/admin/studio/getSavedGameStateFromProduction.html?game=$game&report_id=$reportId&table_id=$tableId",

        // Emulates "load 1" at this table
        "https://studio.boardgamearena.com/table/table/loadSaveState.html?table=$tableId&state=1",

        // Calls the function below to update SQL
        "https://studio.boardgamearena.com/1/$game/$game/loadBugSQL.html?table=$tableId&report_id=$reportId",

        // Emulates "clear PHP cache" in control panel
        // Needed at the end because BGA is caching player info
        "https://studio.boardgamearena.com/admin/studio/clearGameserverPhpCache.html?game=$game",
      ]
    ]);
  }

  /*
   * loadBugSQL: in studio, this is one of the URLs triggered by loadBug() above
   */
  public function loadBugSQL($reportId)
  {
    if ($this->getBgaEnvironment() != 'studio') {
      throw new Exception("Only available at Studio");
    }

    $studioPlayer = self::getCurrentPlayerId();
    $players = self::getObjectListFromDb("SELECT player_id FROM player", true);

    // Change for your game
    // We are setting the current state to match the start of a player's turn if it's already game over
    $sql = [
      "UPDATE global SET global_value=2 WHERE global_id=1 AND global_value=99"
    ];
    foreach ($players as $pId) {
      // All games can keep this SQL
      $sql[] = "UPDATE player SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE global SET global_value=$studioPlayer WHERE global_value=$pId";
      $sql[] = "UPDATE stats SET stats_player_id=$studioPlayer WHERE stats_player_id=$pId";

      // Add game-specific SQL update the tables for your game
      $sql[] = "UPDATE global_variables SET value='$studioPlayer' WHERE value=$pId";
      $sql[] = "UPDATE card SET card_location='hand_$studioPlayer' WHERE card_location='hand_$pId'";

      $studioPlayer++;
    }
    $msg = "<b>Loaded <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a></b><hr><ul><li>" . implode(';</li><li>', $sql) . ';</li></ul>';
    self::warn($msg);
    self::notifyAllPlayers('message', $msg, []);

    foreach ($sql as $q) {
      self::DbQuery($q);
    }

    self::reloadPlayersBasicInfos();
  }
}
