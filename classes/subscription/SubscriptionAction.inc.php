<?php

/**
 * @file SubscriptionAction.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionAction
 * @ingroup subscriptions
 *
 * Common actions for subscription management functions. 
 */

// $Id$

class SubscriptionAction {
	/**
	 * Display subscriptions summary page for the current journal.
	 */
	function subscriptionsSummary() {
		$journal =& Request::getJournal();

		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$statusOptions =& $individualSubscriptionDao->getStatusOptions();
		$individualStatus = array();

		foreach ($statusOptions as $status => $localeKey) {
			$statusCount = $individualSubscriptionDao->getStatusCount($status);
			$individualStatus[] = array(
										"status" => $status,
										"count" => $statusCount,
										"localeKey" => $localeKey
									);		
		}

		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$statusOptions =& $institutionalSubscriptionDao->getStatusOptions();
		$institutionalStatus = array();

		foreach ($statusOptions as $status => $localeKey) {
			$statusCount = $institutionalSubscriptionDao->getStatusCount($status);
			$institutionalStatus[] = array(
										"status" => $status,
										"count" => $statusCount,
										"localeKey" => $localeKey
									);		
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('individualStatus', $individualStatus);
		$templateMgr->assign_by_ref('institutionalStatus', $institutionalStatus);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

		$templateMgr->display('subscription/subscriptionsSummary.tpl');
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 */
	function subscriptions($institutional = false) {
		$journal =& Request::getJournal();
		$rangeInfo =& PKPHandler::getRangeInfo('subscriptions');

		if ($institutional) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			$templateFile = 'subscription/institutionalSubscriptions.tpl';
			$fieldOptions = SubscriptionAction::getInstitutionalSearchFieldOptions();
		} else {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
			$templateFile = 'subscription/individualSubscriptions.tpl';
			$fieldOptions = SubscriptionAction::getIndividualSearchFieldOptions();
		}

		// Subscription status
		$statusOptions =& $subscriptionDao->getStatusOptions();
		$filterStatus = Request::getUserVar('filterStatus') == 0 ? null : Request::getUserVar('filterStatus');

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$subscriptions =& $subscriptionDao->getSubscriptionsByJournalId($journal->getJournalId(), $filterStatus, $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('subscriptions', $subscriptions);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

		// Set search parameters
		foreach (SubscriptionAction::getSearchFormDuplicateParameters() as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('filterStatus', Request::getUserVar('filterStatus'));
		$templateMgr->assign('statusOptions', array(0 => 'manager.subscriptions.allStatus') + $statusOptions);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		$templateMgr->assign('dateFieldOptions', SubscriptionAction::getDateFieldOptions());

		$templateMgr->display($templateFile);
	}

	/**
	 * Get the list of parameter names that should be duplicated when
	 * displaying the search form (i.e. made available to the template
	 * based on supplied user data).
	 * @return array
	 */
	function getSearchFormDuplicateParameters() {
		return array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
	}

	/**
	 * Get the list of individual fields that can be searched by contents.
	 * @return array
	 */
	function getIndividualSearchFieldOptions() {
		return array(
			SUBSCRIPTION_USER => 'manager.subscriptions.user',
			SUBSCRIPTION_MEMBERSHIP => 'manager.subscriptions.membership',
			SUBSCRIPTION_REFERENCE_NUMBER => 'manager.subscriptions.referenceNumber',
			SUBSCRIPTION_NOTES => 'manager.subscriptions.notes'
		);
	}

	/**
	 * Get the list of institutional fields that can be searched by contents.
	 * @return array
	 */
	function getInstitutionalSearchFieldOptions() {
		return array(
			SUBSCRIPTION_INSTITUTION_NAME => 'manager.subscriptions.institutionName',
			SUBSCRIPTION_USER => 'manager.subscriptions.contact',
			SUBSCRIPTION_DOMAIN => 'manager.subscriptions.domain',
			SUBSCRIPTION_IP_RANGE => 'manager.subscriptions.ipRange',
			SUBSCRIPTION_MEMBERSHIP => 'manager.subscriptions.membership',
			SUBSCRIPTION_REFERENCE_NUMBER => 'manager.subscriptions.referenceNumber',
			SUBSCRIPTION_NOTES => 'manager.subscriptions.notes'
		);
	}

	/**
	 * Get the list of date fields that can be searched.
	 * @return array
	 */
	function getDateFieldOptions() {
		return array(
			SUBSCRIPTION_DATE_START => 'manager.subscriptions.dateStartSearch',
			SUBSCRIPTION_DATE_END => 'manager.subscriptions.dateEndSearch'
		);
	}

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args, $institutional = false) {
		$journal =& Request::getJournal();
		$subscriptionId = empty($args[0]) ? null : (int) $args[0];

		if ($institutional) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		// Ensure subscription is for this journal
		if ($subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) {
			$subscriptionDao->deleteSubscriptionById($subscriptionId);
		}
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array second parameter is the ID of the subscription to edit
	 */
	function editSubscription($args, $institutional = false) {
		$journal =& Request::getJournal();
		$userId = Request::getUserVar('userId') == null ? null : (int) Request::getUserVar('userId');
		$subscriptionId = empty($args[0]) ? null : (int) $args[0];

		if ($institutional) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		// Ensure subscription is valid and for this journal
		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) || ($subscriptionId == null && $userId)) {
			$templateMgr =& TemplateManager::getManager();
			$subscriptionCreated = Request::getUserVar('subscriptionCreated') == 1 ? 1 : 0;
			$templateMgr->assign('subscriptionCreated', $subscriptionCreated);

			if ($subscriptionId == null) {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
			} else {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');	
			}

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			if ($institutional) {
				import('subscription.form.InstitutionalSubscriptionForm');
				$subscriptionForm =& new InstitutionalSubscriptionForm($subscriptionId, $userId);
			} else {
				import('subscription.form.IndividualSubscriptionForm');
				$subscriptionForm =& new IndividualSubscriptionForm($subscriptionId, $userId);
			}
			$subscriptionForm->initData();
			$subscriptionForm->display();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription($args, $institutional = false) {
		SubscriptionAction::editSubscription($args, $institutional);
	}

	/**
	 * Display a list of users from which to choose a subscriber/subscription contact.
	 */
	function selectSubscriber($args = array(), $institutional = false) {
		$templateMgr =& TemplateManager::getManager();

		if ($institutional) {
			$pageTitle = 'manager.subscriptions.selectContact';
			$redirect = 'institutional';
		} else {
			$pageTitle = 'manager.subscriptions.selectUser';
			$redirect = 'individual';
		}

		$userDao =& DAORegistry::getDAO('UserDAO');

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users');

		$users =& $userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		import('security.Validation');
		$templateMgr->assign('isJournalManager', Validation::isJournalManager());

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');
		$templateMgr->assign('subscriptionId', Request::getUserVar('subscriptionId'));
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('redirect', $redirect);
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->display('subscription/users.tpl');
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription($args, $institutional = false) {
		$journal =& Request::getJournal();
		$subscriptionId = Request::getUserVar('subscriptionId') == null ? null : (int) Request::getUserVar('subscriptionId');

		if ($institutional) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) || $subscriptionId == null) {

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			if ($institutional) {
				import('subscription.form.InstitutionalSubscriptionForm');
				$subscriptionForm =& new InstitutionalSubscriptionForm($subscriptionId);
			} else {
				import('subscription.form.IndividualSubscriptionForm');
				$subscriptionForm =& new IndividualSubscriptionForm($subscriptionId);
			}
			$subscriptionForm->readInputData();

			// Check for any special cases before trying to save
			if (Request::getUserVar('addIpRange')) {
				$editData = true;
				$ipRanges = $subscriptionForm->getData('ipRanges');
				$ipRanges[] = '';
				$subscriptionForm->setData('ipRanges', $ipRanges);

			} else if (($delIpRange = Request::getUserVar('delIpRange')) && count($delIpRange) == 1) {
				$editData = true;
				list($delIpRange) = array_keys($delIpRange);
				$delIpRange = (int) $delIpRange;
				$ipRanges = $subscriptionForm->getData('ipRanges');
				array_splice($ipRanges, $delIpRange, 1);
				$subscriptionForm->setData('ipRanges', $ipRanges);
			}

			if (isset($editData)) {
				$templateMgr =& TemplateManager::getManager();

				if ($subscriptionId == null) {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
				} else {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');	
				}

				$subscriptionForm->display();
			} else {
				if ($subscriptionForm->validate()) {
					$subscriptionForm->execute();
					return true;
				} else {
					$templateMgr =& TemplateManager::getManager();

					if ($subscriptionId == null) {
						$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
					} else {
						$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');	
					}

					$subscriptionForm->display();
					return false;
				}
			}
		}
	}

	/**
	 * Display a list of subscription types for the current journal.
	 */
	function subscriptionTypes() {
		$journal =& Request::getJournal();
		$rangeInfo =& Handler::getRangeInfo('subscriptionTypes');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByJournalId($journal->getJournalId(), $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('subscriptionTypes', $subscriptionTypes);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

		$templateMgr->display('subscription/subscriptionTypes.tpl');
	}

	/**
	 * Rearrange the order of subscription types.
	 */
	function moveSubscriptionType($args) {
		$subscriptionTypeId = isset($args[0])?$args[0]:0;
		$journal =& Request::getJournal();

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscriptionTypeId);

		if ($subscriptionType && $subscriptionType->getJournalId() == $journal->getJournalId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$subscriptionType->setSequence($subscriptionType->getSequence()+($isDown?1.5:-1.5));
			$subscriptionTypeDao->updateSubscriptionType($subscriptionType);
			$subscriptionTypeDao->resequenceSubscriptionTypes($subscriptionType->getJournalId());
		}
	}

	/**
	 * Delete a subscription type.
	 * @param $args array first parameter is the ID of the subscription type to delete
	 */
	function deleteSubscriptionType($args) {
		$subscriptionTypeId = isset($args[0])?$args[0]:0;
		$journal =& Request::getJournal();

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');

		// Ensure subscription type is for this journal
		if ($subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) {
			$subscriptionTypeDao->deleteSubscriptionTypeById($subscriptionTypeId);
		}
	}

	/**
	 * Display form to edit a subscription type.
	 * @param $args array optional, first parameter is the ID of the subscription type to edit
	 */
	function editSubscriptionType($args) {
		$journal =& Request::getJournal();
		$subscriptionTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');

		// Ensure subscription type is valid and for this journal
		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) || $subscriptionTypeId == null) {

			import('subscription.form.SubscriptionTypeForm');

			$templateMgr =& TemplateManager::getManager();
			$subscriptionTypeCreated = Request::getUserVar('subscriptionTypeCreated') == 1 ? 1 : 0;
			$templateMgr->assign('subscriptionTypeCreated', $subscriptionTypeCreated);

			if ($subscriptionTypeId == null) {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
			} else {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');	
			}

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$subscriptionTypeForm =& new SubscriptionTypeForm($subscriptionTypeId);
			if ($subscriptionTypeForm->isLocaleResubmit()) {
				$subscriptionTypeForm->readInputData();
			} else {
				$subscriptionTypeForm->initData();
			}
			$subscriptionTypeForm->display();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display form to create new subscription type.
	 */
	function createSubscriptionType() {
		SubscriptionAction::editSubscriptionType();
	}

	/**
	 * Save changes to a subscription type.
	 */
	function updateSubscriptionType() {
		import('subscription.form.SubscriptionTypeForm');

		$journal =& Request::getJournal();
		$subscriptionTypeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');

		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) || $subscriptionTypeId == null) {

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$subscriptionTypeForm =& new SubscriptionTypeForm($subscriptionTypeId);
			$subscriptionTypeForm->readInputData();

			if ($subscriptionTypeForm->validate()) {
				$subscriptionTypeForm->execute();
				return true;
			} else {
				$templateMgr =& TemplateManager::getManager();

				if ($subscriptionTypeId == null) {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
				} else {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');	
				}

				$subscriptionTypeForm->display();
				return false;
			}
		} 
	}

	/**
	 * Display subscription policies for the current journal.
	 */
	function subscriptionPolicies() {
		import('subscription.form.SubscriptionPolicyForm');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$subscriptionPolicyForm =& new SubscriptionPolicyForm();
		if ($subscriptionPolicyForm->isLocaleResubmit()) {
			$subscriptionPolicyForm->readInputData();
		} else {
			$subscriptionPolicyForm->initData();
		}
		$subscriptionPolicyForm->display();
	}

	/**
	 * Save subscription policies for the current journal.
	 */
	function saveSubscriptionPolicies($args = array()) {
		import('subscription.form.SubscriptionPolicyForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$subscriptionPolicyForm =& new SubscriptionPolicyForm();
		$subscriptionPolicyForm->readInputData();

		if ($subscriptionPolicyForm->validate()) {
			$subscriptionPolicyForm->execute();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');
			$templateMgr->assign('subscriptionPoliciesSaved', '1');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$subscriptionPolicyForm->display();
		} else {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$subscriptionPolicyForm->display();
		}
	}
}

?>