<?php

class ExtUserFunctions {

	/**
	 * @param $parser Parser
	 * @return bool
	 */
	public static function clearState( $parser ) {
		$parser->pf_ifexist_breakdown = array();
		return true;
	}

	/**
	 * Register ParserClearState hook.
	 * We defer this until needed to avoid the loading of the code of this file
	 * when no parser function is actually called.
	 */
	public static function registerClearHook() {
		static $done = false;
		if( !$done ) {
			global $wgHooks;
			$wgHooks['ParserClearState'][] = __CLASS__ . '::clearState';
			$done = true;
		}
	}

	/**
	 * @return User
	 * Using $wgUser Incompatibility with SMW using via $parser
	 **/
	private static function getUserObj() {
		global $wgUser;
		return $wgUser;
	}

	/**
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $args array
	 * @return string
	 */
	public static function ifanonObj( $parser, $frame, $args ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		if( $pUser->isAnon() ){
			return isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		} else {
			return isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		}
	}

	/**
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $args array
	 * @return string
	 */
	public static function ifblockedObj( $parser, $frame, $args ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		if( $pUser->isBlocked() ){
			return isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		} else {
			return isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		}
	}

	/**
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $args array
	 * @return string
	 */
	public static function ifsysopObj( $parser, $frame, $args ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		if( $pUser->isAllowed( 'protect' ) ){
			return isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		} else {
			return isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		}
	}

	/**
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $args array
	 * @return string
	 */
	public static function ifingroupObj ( $parser, $frame, $args ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		$grp = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';

		if( $grp!=='' ) {
			# Considering multiple groups
			$allgrp = explode(",", $grp);

			$userGroups = $pUser->getEffectiveGroups();
			foreach ( $allgrp as $elgrp ) {
				if ( in_array( trim( $elgrp ), $userGroups ) ) {
					return isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
				}
			}
		}
		return isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
	}

	/**
	 * @param $parser Parser
	 * @param $alt string
	 * @return String
	 */
	public static function realname( $parser, $alt = '' ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		if( $pUser->isAnon() && $alt !== '' ) {
			return $alt;
		}
		return $pUser->getRealName();
	}

	/**
	 * @param $parser Parser
	 * @param $alt string
	 * @return String
	 */
	public static function username( $parser, $alt = '' ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		if( $pUser->isAnon() && $alt !== '' ) {
			return $alt;
		}
		return $pUser->getName();
	}

	/**
	 * @param $parser Parser
	 * @param $alt string
	 * @return String
	 */
	public static function useremail( $parser, $alt = '' ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		if($pUser->isAnon() && $alt!=='') {
			return $alt;
		}
		return $pUser->getEmail();
	}

	/**
	 * @param $parser Parser
	 * @param $alt string
	 * @return String
	 */
	public static function nickname( $parser, $alt = '' ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$pUser = self::getUserObj();

		if( $pUser->isAnon() ) {
			if ( $alt !== '' ) {
				return $alt;
			}
			return $pUser->getName();
		}
		$nickname = $pUser->getOption( 'nickname' );
		$nickname = $nickname === '' ? $pUser->getName() : $nickname;
		return $nickname;
	}

	/**
	 * @param $parser Parser
	 * @return string
	 */
	public static function ip( $parser ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$request = self::getUserObj()->getRequest();
		return $request->getIP();
	}

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgUFEnablePersonalDataFunctions, $wgUFAllowedNamespaces, $wgUFEnableSpecialContexts;

		// Whether it's a Special Page or a Maintenance Script
		$special = false;

		// Initialize NS
		$title = RequestContext::getMain()->getTitle();
		$cur_ns = $title === null ? -1 : $title->getNamespace();
		if ( $cur_ns == -1 ) {
			$special = true;
		}

		$process = false;

		// As far it's not special case, check if current page NS is in the allowed list
		if ( !$special ) {
			if ( isset( $wgUFAllowedNamespaces[$cur_ns] ) ) {
				if ( $wgUFAllowedNamespaces[$cur_ns] ) {
					$process = true;
				}
			}
		} elseif ( $wgUFEnableSpecialContexts ) {
			if ( $special ) {
				$process = true;
			}
		}

		if ( $process ) {
			// These functions accept DOM-style arguments

			$parser->setFunctionHook( 'ifanon', [ __CLASS__, 'ifanonObj' ], Parser::SFH_OBJECT_ARGS );
			$parser->setFunctionHook( 'ifblocked', [ __CLASS__, 'ifblockedObj' ], Parser::SFH_OBJECT_ARGS );
			$parser->setFunctionHook( 'ifsysop', [ __CLASS__, 'ifsysopObj' ], Parser::SFH_OBJECT_ARGS );
			$parser->setFunctionHook( 'ifingroup', [ __CLASS__, 'ifingroupObj' ], Parser::SFH_OBJECT_ARGS );

			if ( $wgUFEnablePersonalDataFunctions ) {
				$parser->setFunctionHook( [ __CLASS__, 'realname' ], 'realname' );
				$parser->setFunctionHook( [ __CLASS__, 'username' ], 'username' );
				$parser->setFunctionHook( [ __CLASS__, 'useremail' ], 'useremail' );
				$parser->setFunctionHook( [ __CLASS__, 'nickname' ], 'nickname' );
				$parser->setFunctionHook( [ __CLASS__, 'ip' ], 'ip' );
			}
		}
	}
}
