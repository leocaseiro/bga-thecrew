<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * The Crew implementation : © Nicolas Gocel <nicolas.gocel@gmail.com> & Timothée Pecatte <tim.pecatte@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * thecrew.action.php
 *
 * thecrew main action entry point
 *
 */


class action_thecrew extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if( self::isArg( 'notifwindow') ) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
    } else {
      $this->view = "thecrew_thecrew";
      self::trace( "Complete reinitialization of board game" );
    }
  }

  public function actChooseTask()
  {
    self::setAjaxMode();
    $taskId = self::getArg("taskId", AT_posint, true);
    $this->game->actChooseTask($taskId);
    self::ajaxResponse();
  }

  public function actPlayCard()
  {
    self::setAjaxMode();
    $cardId = self::getArg("cardId", AT_posint, true);
    $this->game->actPlayCard( $cardId);
    self::ajaxResponse( );
  }


  public function actContinueMissions()
  {
    self::setAjaxMode();
    $this->game->actContinueMissions();
    self::ajaxResponse( );
  }

  public function actStopMissions()
  {
    self::setAjaxMode();
    $this->game->actStopMissions();
    self::ajaxResponse( );
  }


/***********************
******* QUESTION *******
***********************/
  public function actReply()
  {
    self::setAjaxMode();
    $reply = self::getArg("reply", AT_posint, true );
    $this->game->actReply($reply);
    self::ajaxResponse();
  }


/***********************
******* DISTRESS *******
***********************/
  public function actChooseDirection()
  {
    self::setAjaxMode();
    $dir = self::getArg("dir", AT_posint, true);
    $this->game->actChooseDirection($dir);
    self::ajaxResponse();
  }

  public function actChooseCardDistress()
  {
    self::setAjaxMode();
    $cardId = self::getArg("cardId", AT_posint, true);
    $this->game->actChooseCardDistress($cardId);
    self::ajaxResponse( );
  }


/***********************
***** COMMUNICATION ****
***********************/

  public function actToggleComm()
  {
    self::setAjaxMode();
    $this->game->actToggleComm();
    self::ajaxResponse( );
  }

  public function actCancelComm()
  {
    self::setAjaxMode();
    $this->game->actCancelComm();
    self::ajaxResponse( );
  }

  public function actConfirmComm()
  {
    self::setAjaxMode();
    $cardId = self::getArg("cardId", AT_posint, true );
    $status = self::getArg("status", AT_alphanum, true );
    $this->game->actConfirmComm($cardId, $status);
    self::ajaxResponse();
  }



  public function actPickCrew()
  {
    self::setAjaxMode();
    $crewId = self::getArg("pId", AT_posint, true );
    $this->game->actPickCrew( $crewId);
    self::ajaxResponse();
  }



public function actCancel()
{
self::setAjaxMode();
$this->game->actCancel();
self::ajaxResponse( );
}

public function actButton()
{
self::setAjaxMode();

$choice = self::getArg( "choice", AT_alphanum, true );
$this->game->actButton($choice);

self::ajaxResponse( );
}

public function actMultiSelect()
{
self::setAjaxMode();

$id1 = self::getArg( "id1", AT_alphanum, true );
$id2 = self::getArg( "id2", AT_alphanum, true );
$this->game->actMultiSelect($id1 ,$id2);

self::ajaxResponse( );
}

}