<?php

App::uses('TournamentAppModel', 'Tournament.Model');

class Team extends TournamentAppModel {

	// Status
	const DISBANDED = 3;

	/**
	 * Belongs to.
	 *
	 * @type array
	 */
	public $belongsTo = array(
		'Leader' => array(
			'className' => USER_MODEL,
			'foreignKey' => 'user_id'
		)
	);

	/**
	 * Has many.
	 *
	 * @type array
	 */
	public $hasMany = array(
		'TeamMember' => array(
			'className' => 'Tournament.TeamMember',
			'conditions' => array('TeamMember.status >' => self::PENDING),
			'order' => array('TeamMember.status' => 'ASC', 'TeamMember.role' => 'ASC'),
			'dependent' => true,
			'exclusive' => true
		),
		'HomeMatch' => array(
			'className' => 'Tournament.Match',
			'foreignKey' => 'home_id',
			'conditions' => array('HomeMatch.type' => self::TEAM),
			'dependent' => true,
			'exclusive' => true
		),
		'AwayMatch' => array(
			'className' => 'Tournament.Match',
			'foreignKey' => 'away_id',
			'conditions' => array('AwayMatch.type' => self::TEAM),
			'dependent' => true,
			'exclusive' => true
		)
	);

	/**
	 * Has and belongs to many.
	 *
	 * @type array
	 */
	public $hasAndBelongsToMany = array(
		'Event' => array(
			'className' => 'Tournament.Event',
			'with' => 'Tournament.EventParticipant',
			'order' => array('EventParticipant.created' => 'DESC')
		)
	);

	/**
	 * Behaviors.
	 *
	 * @type array
	 */
	public $actsAs = array(
		'Utility.Sluggable' => array(
			'field' => 'name'
		)
	);

	/**
	 * Validation.
	 *
	 * @type array
	 */
	public $validate = array(
		'user_id' => 'notEmpty',
		'status' => 'notEmpty',
		'name' => array(
			'rule' => 'notEmpty',
			'required' => true
		),
		'password' => 'notEmpty',
		'wins' => array(
			'rule' => 'numeric',
			'message' => 'May only contain numerical characters'
		),
		'losses' => array(
			'rule' => 'numeric',
			'message' => 'May only contain numerical characters'
		),
		'ties' => array(
			'rule' => 'numeric',
			'message' => 'May only contain numerical characters'
		),
		'points' => array(
			'rule' => 'numeric',
			'message' => 'May only contain numerical characters'
		)
	);

	/**
	 * Enum mapping.
	 *
	 * @type array
	 */
	public $enum = array(
		'status' => array(
			self::PENDING => 'PENDING',
			self::ACTIVE => 'ACTIVE',
			self::DISABLED => 'DISABLED',
			self::DISBANDED => 'DISBANDED'
		)
	);

	/**
	 * Admin settings.
	 *
	 * @type array
	 */
	public $admin = array(
		'iconClass' => 'icon-group',
		'imageFields' => array('logo')
	);

	/**
	 * Configure Uploader manually.
	 *
	 * @param bool|int $id
	 * @param string $table
	 * @param string $ds
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		$config = Configure::read('Tournament.uploads');
		$transport = $config['transport'];

		if ($transport) {
			$transport['folder'] = 'tournament/teams/';
		}

		$this->actsAs['Uploader.Attachment'] = array(
			'logo' => array(
				'nameCallback' => 'formatFilename',
				'uploadDir' => WWW_ROOT . 'files/tournament/teams/',
				'finalPath' => 'files/tournament/teams/',
				'dbColumn' => 'logo',
				'overwrite' => true,
				'stopSave' => true,
				'allowEmpty' => false,
				'transport' => $transport,
				'transforms' => array(
					'logo' => array(
						'method' => 'crop',
						'width' => $config['teamLogo'][0],
						'height' => $config['teamLogo'][1],
						'self' => true,
						'overwrite' => true
					)
				)
			)
		);

		$this->actsAs['Uploader.FileValidation'] = array(
			'logo' => array(
				'minWidth' => $config['teamLogo'][0],
				'minHeight' => $config['teamLogo'][1],
				'extension' => array('gif', 'jpg', 'jpeg', 'png'),
				'type' => array('image/gif', 'image/jpg', 'image/jpeg', 'image/png'),
				'required' => true
			)
		);

		parent::__construct($id, $table, $ds);
	}

	/**
	 * Disband a team.
	 *
	 * @param int $id
	 * @return mixed
	 */
	public function disband($id) {
		$team = $this->getById($id);

		if (!$team) {
			return true;
		}

		$this->TeamMember->updateAll(
			array('TeamMember.status' => TeamMember::DISBANDED),
			array(
				'TeamMember.team_id' => $id,
				'TeamMember.status <=' => TeamMember::ACTIVE
			)
		);

		$this->id = $id;
		$this->deleteFiles($id); // uploader

		return $this->save(array(
			'name' => sprintf('%s (%s)', $team['Team']['name'], __d('tournament', 'Disbanded')),
			'status' => self::DISBANDED
		), false);
	}

	/**
	 * Get a team profile and related data.
	 *
	 * @param string $slug
	 * @return array
	 */
	public function getTeamProfile($slug) {
		return $this->find('first', array(
			'conditions' => array('Team.slug' => $slug),
			'contain' => array(
				'Leader',
				'TeamMember' => array('Player', 'User'),
				'Event' => array('Game', 'League', 'Division')
			),
			'cache' => array(__METHOD__, $slug)
		));
	}

	/**
	 * Use team slug as logo name.
	 *
	 * @param string $name
	 * @param \Transit\File $file
	 * @return string
	 */
	public function formatFilename($name, $file) {
		if ($this->id) {
			if ($team = $this->getById($this->id)) {
				return $team['Team']['slug'];
			}
		}

		return parent::formatFilename($name, $file);
	}

	/**
	 * Set required for both create and update.
	 *
	 * @param array $options
	 * @return bool
	 */
	public function beforeValidate($options = array()) {
		unset($this->validate['logo']['required']['on']);

		return true;
	}

}