<?php
//
// Definition of eZWorkflowProcess class
//
// Created on: <16-Apr-2002 11:08:14 amos>
//
// Copyright (C) 1999-2002 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/home/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*!
  \class eZWorkflowProcess ezworkflowprocess.php
  \brief

*/

include_once( 'lib/ezdb/classes/ezdb.php' );
include_once( 'kernel/classes/ezpersistentobject.php' );
include_once( 'kernel/classes/ezworkflowevent.php' );
include_once( 'kernel/classes/ezmodulerun.php' );

class eZWorkflowProcess extends eZPersistentObject
{
    function eZWorkflowProcess( $row )
    {
        $this->eZPersistentObject( $row );
    }

    function &definition()
    {
        return array( 'fields' => array( 'id' => 'ID',
                                         'process_key' => 'ProcessKey',
                                         'workflow_id' => 'WorflowID',
                                         'user_id' => 'UserID',
                                         'content_id' => 'ContentID',
                                         'content_version' => 'ContentVersion',
                                         'session_key' => 'SessionKey',
                                         'node_id' => 'NodeID',
                                         'event_id' => 'EventID',
                                         'event_position' => 'EventPosition',
                                         'event_state' => 'EventState',
                                         'last_event_id' => 'LastEventID',
                                         'last_event_position' => 'LastEventPosition',
                                         'last_event_status' => 'LastEventStatus',
                                         'event_status' => 'EventStatus',
                                         'created' => 'Created',
                                         'modified' => 'Modified',
                                         'activation_date' => 'ActivationDate',
                                         'status' => 'Status',
                                         'parameters' => 'Parameters',
                                         'memento_key' => 'MementoKey' ),
                      'keys' => array( 'id' ),
                      "increment_key" => "id",
                      'class_name' => 'eZWorkflowProcess',
                      'sort' => array( 'user_id' => 'asc' ),
                      'name' => 'ezworkflow_process' );
    }

    function &create( $processKey, $parameters )
//                      $workflowID, $userID,
//                      $contentID, $contentVersion, $nodeID, $sessionKey = '' )
    {
        include_once( 'lib/ezlocale/classes/ezdatetime.php' );
        $dateTime = eZDateTime::currentTimeStamp();

        $row = array( 'process_key' => $processKey,
                      'workflow_id' => $parameters['workflow_id'],
                      'user_id' => $parameters['user_id'],
                      'content_id' => 0,
                      'content_version' => 0,
                      'node_id' => 0,
                      'session_key' => 0,
                      'event_id' => 0,
                      'event_position' => 0,
                      'last_event_id' => 0,
                      'last_event_position' => 0,
                      'last_event_status' => 0,
                      'event_status' => 0,
                      'created' => $dateTime,
                      'modified' => $dateTime,
                      'parameters' => serialize( $parameters ) );
        return new eZWorkflowProcess( $row );
    }

    function reset()
    {
        $this->EventID = 0;
        $this->EventPosition = 0;
        $this->LastEventID = 0;
        $this->LastEventPosition = 0;
        $this->LastEventStatus = 0;
        $this->ActivationDate = 0;
        $this->EventStatus = 0;
    }

    function advance( $next_event_id = 0, $next_event_pos = 0, $status = 0 )
    {
        $this->LastEventID = $this->EventID;
        $this->LastEventPosition = $this->EventPosition;
        $this->LastEventStatus = $status;
        $this->EventID =  $next_event_id;
        $this->EventPosition = $next_event_pos;
        $this->ActivationDate = 0;
    }


    function run( &$workflow, &$workflowEvent, &$eventLog )
    {
        $eventLog = array();
        eZDebug::writeNotice( $workflowEvent, "workflowEvent in process->run beginning" );
        include_once( "lib/ezlocale/classes/ezdatetime.php" );

        $runCurrentEvent = true;
        $done = false;
        $workflowStatus = $this->attribute( 'status' );
        eZDebug::writeNotice( $workflowStatus , 'workflowStatus' );
        $lastEventStatus = $this->attribute( 'last_event_status' );

// just temporary. needs to be removed from parameters
        if ( $workflowEvent == null )
        {
            $workflowEvent =& eZWorkflowEvent::fetch( $this->attribute( 'event_id' ) );
        }

        switch( $lastEventStatus )
        {
            case EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON:
            case EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON_REPEAT:
            case EZ_WORKFLOW_TYPE_STATUS_FETCH_TEMPLATE:
            case EZ_WORKFLOW_TYPE_STATUS_FETCH_TEMPLATE_REPEAT:
            case EZ_WORKFLOW_TYPE_STATUS_REDIRECT:
            case EZ_WORKFLOW_TYPE_STATUS_REDIRECT_REPEAT:
            {
                $activationDate = $this->attribute( "activation_date" );
                eZDebug::writeNotice( "Checking activation date" );
                if ( eZDateTime::currentTimeStamp() < $activationDate )
                {
                    eZDebug::writeNotice( "Date failed, not running events" );
                    $eventType =& $workflowEvent->eventType();
                    $eventLog[] = array( "status" => $lastEventStatus,
                                         "status_text" => eZWorkflowType::statusName( $lastEventStatus ),
                                         "information" => $eventType->attribute( "information" ),
                                         "description" => $workflowEvent->attribute( "description" ),
                                         "type_name" => $eventType->attribute( "name" ),
                                         "type_group" => $eventType->attribute( "group_name" ) );
                    $done = true;
                }
                else
                {
                    eZDebug::writeNotice( "Date ok, running events" );
                    eZDebug::writeNotice( $lastEventStatus, 'WORKFLOW_TYPE_STATUS' );
                    if ( $lastEventStatus == EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON ||
                         $lastEventStatus == EZ_WORKFLOW_TYPE_STATUS_FETCH_TEMPLATE   ||
                         $lastEventStatus == EZ_WORKFLOW_TYPE_STATUS_REDIRECT )
                    {
                        $runCurrentEvent = false;
                    }
                }
            } break;
            default:
                break;
        }

        eZDebug::writeNotice( $workflowEvent, "$done , $runCurrentEvent , fooooooooooooooo" );
        eZDebug::writeNotice( $done , "$done , $runCurrentEvent , fooooooooooooooo" );
        eZDebug::writeNotice( $runCurrentEvent, "$done , $runCurrentEvent , fooooooooooooooo" );
        while ( !$done )
        {
//            var_dump( $done );
//            var_dump( $runCurrentEvent );
            flush();
            if ( $runCurrentEvent )
            {
                eZDebug::writeNotice( "runCurrentEvent is true" );
            }
            else
            {
                eZDebug::writeNotice( "runCurrentEvent is false" );
            }
            if ( $workflowEvent != null )
            {
                eZDebug::writeNotice( $workflowEvent ,"workflowEvent  is not null" );
            }
            else
            {
                eZDebug::writeNotice( $workflowEvent ,"workflowEvent  is  null" );
            }
            if ( get_class( $workflowEvent ) == "ezworkflowevent")
            {
                eZDebug::writeNotice( get_class( $workflowEvent )  ,"workflowEvent class  is ezworkflowevent " );
            }
            else
            {
                eZDebug::writeNotice( get_class( $workflowEvent )  ,"workflowEvent class  is not ezworkflowevent " );
            }
            eZDebug::writeNotice( $done , "in while" );
            if ( $runCurrentEvent and
                 $workflowEvent !== null and
                 get_class( $workflowEvent ) == "ezworkflowevent" )
            {

                eZDebug::writeNotice( $workflowEvent , "workflowEvent line 173 " );
                $eventType =& $workflowEvent->eventType();
                eZDebug::writeNotice( $eventType, "eventType line 176 " );
                if ( is_subclass_of( $eventType, "ezworkflowtype" ) )
                {
                    $lastEventStatus = $eventType->execute( $this, $workflowEvent );
// code idea :
                    $workflowParameters =& $this->attribute( 'parameter_list' );

                    $cleanupList =& $workflowParameters['cleanup_list'];
                    if ( $eventType->needCleanup() )
                    {
                        $cleanupList[] = $workflowEvent->attribute( 'id' );
                        $this->setAttribute( 'parameters', serialize( $workflowParameters ) );
                    }
//                    print( "<br>lastEventStatus" . $lastEventStatus );
                    eZDebug::writeNotice( $lastEventStatus, "lastEventStatus" );
                    switch( $lastEventStatus )
                    {
                        case EZ_WORKFLOW_TYPE_STATUS_ACCEPTED:
                        {
                            $done = false;
                            $workflowStatus = EZ_WORKFLOW_STATUS_DONE;
                        }break;
                        case EZ_WORKFLOW_TYPE_STATUS_WORKFLOW_DONE:
                        {
                            $done = true;
                            $workflowStatus = EZ_WORKFLOW_STATUS_DONE;
                        } break;
                        case EZ_WORKFLOW_TYPE_STATUS_REJECTED:
                        {
                            $done = true;
                            $workflowStatus = EZ_WORKFLOW_STATUS_FAILED;
                        } break;
                        case EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON:
                        case EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON_REPEAT:
                        {
                            $date = $eventType->attribute( "activation_date" );
                            $this->setAttribute( "activation_date", $date );
                            $workflowStatus = EZ_WORKFLOW_STATUS_DEFERRED_TO_CRON;
                            $done = true;
                        } break;
                        case EZ_WORKFLOW_TYPE_STATUS_FETCH_TEMPLATE:
                        case EZ_WORKFLOW_TYPE_STATUS_FETCH_TEMPLATE_REPEAT:
                        {
                            $workflowStatus = EZ_WORKFLOW_STATUS_FETCH_TEMPLATE;
                            $done = true;
                        } break;
                        case EZ_WORKFLOW_TYPE_STATUS_REDIRECT:
                        case EZ_WORKFLOW_TYPE_STATUS_REDIRECT_REPEAT:
                        {
                            $workflowStatus = EZ_WORKFLOW_STATUS_REDIRECT;
                            $done = true;
                        } break;
                        case EZ_WORKFLOW_TYPE_STATUS_RUN_SUB_EVENT:
                        {
                            eZDebug::writeWarning( "Run sub event not supported yet", "eZWorkflowProcess::run" );
                        } break;
                        case EZ_WORKFLOW_TYPE_STATUS_WORKFLOW_CANCELLED:
                        {
                            $done = true;
                            $this->advance();
                            $workflowStatus = EZ_WORKFLOW_STATUS_CANCELLED;
                        } break;
                        default:
                        {
                            eZDebug::writeWarning( "Unknown status '$lastEventStatus'", "eZWorkflowProcess::run" );
                        } break;
                    }
                    $eventLog[] = array( "status" => $lastEventStatus,
                                         "status_text" => eZWorkflowType::statusName( $lastEventStatus ),
                                         "information" => $eventType->attribute( "information" ),
                                         "description" => $workflowEvent->attribute( "description" ),
                                         "type_name" => $eventType->attribute( "name" ),
                                         "type_group" => $eventType->attribute( "group_name" ) );
                }
                else
                    eZDebug::writeError( "Expected an eZWorkFlowType object", "eZWorkflowProcess::run" );
            }
            else
            {
                eZDebug::writeNotice( "Not running current event. Trying next" );
            }
            $runCurrentEvent = true;
            // still not done
            if ( !$done )
            {
                // fetch next event
                $event_pos = $this->attribute( "event_position" );
                eZDebug::writeNotice( $this , 'workflow_process' );
                $next_event_pos = $event_pos + 1;
                if ( !is_object( $workflow ) )
                {
                    eZDebug::printReport();
                }
                $next_event_id = $workflow->fetchEventIndexed( $next_event_pos );
                eZDebug::writeNotice( $event_pos , "workflow  not done");

                if ( $next_event_id !== null )
                {
                    $this->advance( $next_event_id, $next_event_pos, $lastEventStatus );
                    $workflowEvent =& eZWorkflowEvent::fetch( $next_event_id );
                }
                else
                {
                    $done = true;
                    unset( $workflowEvent );
                    eZDebug::writeNotice( $this, "workflow done");
                    $workflowStatus = EZ_WORKFLOW_STATUS_DONE;
                    $this->advance();
                }
            }

        }

        $this->setAttribute( "last_event_status", $lastEventStatus );
        $this->setAttribute( "status", $workflowStatus );
        $this->setAttribute( "modified", eZDateTime::currentTimeStamp() );
        return $workflowStatus;
    }

    function remove()
    {
        eZPersistentObject::remove();
    }

    function store()
    {
        eZPersistentObject::store();
    }

    function &fetch( $id, $asObject = true )
    {
        return eZPersistentObject::fetchObject( eZWorkflowProcess::definition(),
                                                null,
                                                array( 'id' => $id ),
                                                $asObject );
    }

    function &fetchList( $conds = null, $asObject = true )
    {
        return eZPersistentObject::fetchObjectList( eZWorkflowProcess::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }

    function createKey( $parameters, $keys = null )
    {

        $string = '';
        if ( $keys != null )
        {
            foreach ( $keys as $key )
            {
                $value = $parameters[$key];
                $string .= $key . $value;
            }
        }else
        {
            foreach ( array_keys( $parameters ) as $key )
            {
                $value =& $parameters[$key];
                $string .= $key . $value;
            }
        }
        eZDebug::writeDebug( $string ,"precess key string" );
        return md5( $string );
    }



    function fetchListByKey( $searchKey, $asObject = true )
    {
        return eZPersistentObject::fetchObjectList( eZWorkflowProcess::definition(),
                                                    null,
                                                    array( 'process_key' => $searchKey ), null, null,
                                                    $asObject );
    }

    function &fetchUserList( $userID, $asObject = true )
    {
        $conds = array( 'user_id' => $userID );
        return eZPersistentObject::fetchObjectList( eZWorkflowProcess::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }

    function &fetchForContent( $workflowID, $userID,
                               $contentID, $content_version, $nodeID,
                               $asObject = true )
    {
        $conds = array( 'workflow_id' => $workflowID,
                        'user_id' => $userID,
                        'content_id' => $contentID,
                        'content_version' => $contentVersion,
                        'node_id' => $nodeID );
        return eZPersistentObject::fetchObjectList( eZWorkflowProcess::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }
    function &fetchForStatus( $status = EZ_WORKFLOW_STATUS_DEFERRED_TO_CRON,  $asObject = true )
    {
        $conds = array( 'status' => $status );
        return eZPersistentObject::fetchObjectList( eZWorkflowProcess::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }

    function &fetchForSession( $sessionKey, $workflowID, $asObject = true )
    {
        $conds = array( 'workflow_id' => $workflowID,
                        'session_key' => $sessionKey );
        return eZPersistentObject::fetchObjectList( eZWorkflowProcess::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }

//     function &fetchListCount( $version = 0 )
//     {
//         $custom = array( array( 'name' => 'count',
//                                 'operation' => 'count( id )' ) );
//         $lst =& eZPersistentObject::fetchObjectList( eZWorkflowProcess::definition(),
//                                                      array(), array( 'version' => $version ), null, null,
//                                                      false, null,
//                                                      $custom );
//         return $lst[0]['count'];
//     }

    function currentEvent()
    {
    }

    function advanceToNext()
    {
    }

    function attributes()
    {
        return array_merge( eZPersistentObject::attributes(),
                            array( 'user',
                                   'content', 'node',
                                   'workflow', 'workflow_event', 'last_workflow_event', 'parameter_list' ) );
    }

    function &setParameters( $parameterList = null )
    {
        if ( !is_null( $parameterList ) )
        {
            $this->Parameters =& $parameterList;
        }
        $this->setAttribute( 'parameters', serialize( $this->Parameters ) );
        return $this->attribute( 'parameter_list' );
    }
    function hasAttribute( $attr )
    {
        return ( $attr == 'user' or
                 $attr == 'content' or $attr == 'node' or
                 $attr == 'workflow' or $attr == 'workflow_event' or $attr =='last_workflow_event' or
                 $attr == 'parameter_list' or
                 eZPersistentObject::hasAttribute( $attr ) );
    }

    function &attribute( $attr )
    {
        switch( $attr )
        {
            case 'user':
            {
                include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
                $user =& eZUser::instance( $this->UserID );
                return $user;
            } break;
            case 'content':
            {
                include_once( 'kernel/classes/ezcontentobject.php' );
                $content =& eZContentObject::fetch( $this->ContentID );
                return $content;
            } break;
            case 'node':
            {
                include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
                $node =& eZContentObjectTreeNode::fetch( $this->NodeID );
                return $node;
            } break;
            case 'workflow':
            {
                include_once( 'kernel/classes/ezworkflow.php' );
                $workflow =& eZWorkflow::fetch( $this->WorkflowID );
                return $content;
            } break;
            case 'workflow_event':
            {
                include_once( 'kernel/classes/ezworkflowevent.php' );
                $event =& eZWorkflowEvent::fetch( $this->EventID );
                return $event;
            } break;
            case 'last_workflow_event':
            {
                include_once( 'kernel/classes/ezworkflowevent.php' );
                $event =& eZWorkflowEvent::fetch( $this->LastEventID );
                return $event;
            } break;
            case 'parameter_list':
            {
                if ( !isset( $this->ParameterList ) )
                {
                    $this->ParameterList = unserialize( $this->attribute( 'parameters' ) );
                }
                return $this->ParameterList;
            }break;
            default:
                return eZPersistentObject::attribute( $attr );
        }

    }

    function remove()
    {
        $workflowParameters = $this->attribute( 'parameter_list' );
        $cleanupList = array();
        if ( isset( $workflowParameters['cleanup_list'] ) && is_array( $workflowParameters['cleanup_list'] ) )
        {
            $cleanupList = $workflowParameters['cleanup_list'];
            foreach ( array_keys( $cleanupList ) as $key )
            {
                $workflowEventID = $cleanupList[$key];
                $workflowEvent = eZWorkflowEvent::fetch( $workflowEventID );
                $workflowType =& $workflowEvent->eventType();
                $workflowType->cleanup( $this, $workflowEvent );
            }
        }
        eZPersistentObject::removeObject( eZWorkflowProcess::definition(), array( 'id' => $this->attribute( 'id' ) ) );
    }

    /// \privatesection
    var $ID;
    var $WorkflowID;
    var $UserID;
    var $ContentID;
    var $NodeID;
    var $EventID;
    var $EventPosition;
    var $LastEventID;
    var $LastEventPosition;
    var $LastEventStatus;
    var $EventStatus;
    var $Created;
    var $Modified;
    var $ActivationDate;
}

?>
