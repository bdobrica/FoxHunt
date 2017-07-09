<?php
/**
 * Core of FH_*
 */

/**
 * Game Class
 *
 * @category
 * @package FoxHunt
 * @subpackage None
 * @copyright Core Security Advisers SRL
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 0.1
 *
 */
class FH_Game extends FH_Model {
	public static $version = '1.0.0';

	public static $human = 'Game';

	public static $scheme = [];

	const GET		= 'game';

	const BEGIN_GAME	= 0;
	const ROUND0_BEGIN	= 1;
	const ROUND0_END	= 2;
	const ROUND1_BEGIN	= 3;
	const ROUND1_END	= 4;
	const ROUND2_BEGIN	= 5;
	const ROUND2_END	= 6;
	const ROUND3_BEGIN	= 7;
	const ROUND3_END	= 8;
	const ROUND4_BEGIN	= 9;
	const ROUND4_END	= 10;
	const GAME_REPORT	= 11;
	const END_GAME		= 12;
	const CANCELED_GAME	= 13;

	const MAX_MEETINGS	= 3;

	const NEGOTIATION_0	= 0;
	const NEGOTIATION_1	= 1;
	const NEGOTIATION_2	= 2;
	const NEGOTIATION_3	= 3;

	public static $T = 'fh_games';

	protected static $K = [
		'owner',
		'locale',
		'name',
		'players',
		'scenario',
		'state',
		'state_data',
		'negotiation',
		'market',
		'variables',
		'scores',
		'active',
		'ended',
		'stamp'
		];

	protected static $Q = [
		'`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT',
		'`owner` int(11) NOT NULL DEFAULT 0',
		'`locale` varchar(10) NOT NULL DEFAULT \'\'',
		'`name` varchar(64) NOT NULL DEFAULT \'\'',
		'`players` int(2) NOT NULL DEFAULT 4',
		'`scenario` varchar(128) NOT NULL DEFAULT \'default\'',
		'`state` int(11) NOT NULL DEFAULT 0',
		'`state_data` text NOT NULL',
		'`negotiation` int(11) NOT NULL DEFAULT 0',
		'`market` text NOT NULL',
		'`variables` text NOT NULL',
		'`scores` text NOT NULL',
		'`active` int(1) NOT NULL DEFAULT 0',
		'`ended` int(11) NOT NULL DEFAULT 0',
		'`stamp` int(11) NOT NULL DEFAULT 0'
		];

	public static $S = [
		self::BEGIN_GAME	=> /*T[*/'Game Started'/*]*/,
		self::ROUND0_BEGIN	=> /*T[*/'Begin Registration'/*]*/,
		self::ROUND0_END	=> /*T[*/'End Registration'/*]*/,
		self::ROUND1_BEGIN	=> /*T[*/'Begin Conversations'/*]*/,
		self::ROUND1_END	=> /*T[*/'End Conversations'/*]*/,
		self::ROUND2_BEGIN	=> /*T[*/'Begin Offering'/*]*/,
		self::ROUND2_END	=> /*T[*/'End Offering'/*]*/,
		self::ROUND3_BEGIN	=> /*T[*/'Begin Presentations'/*]*/,
		self::ROUND3_END	=> /*T[*/'End Presentations'/*]*/,
		self::ROUND4_BEGIN	=> /*T[*/'Begin Negotiation'/*]*/,
		self::ROUND4_END	=> /*T[*/'End Negotiation'/*]*/,
		self::GAME_REPORT	=> /*T[*/'Game Report'/*]*/,
		self::END_GAME		=> /*T[*/'Game Ended'/*]*/,
		self::CANCELED_GAME	=> /*T[*/'Game Canceled'/*]*/,
		];

	/**
	 * The player can login only on this rounds:
	 */
	public static $PL = [ /* self::ROUND0_BEGIN, */ self::ROUND1_BEGIN, self::ROUND2_BEGIN, self::ROUND3_BEGIN, self::ROUND4_BEGIN, self::GAME_REPORT ];

	/**
	 * The order of the rounds
	 */
	public static $SO = [
		self::BEGIN_GAME,
/*		self::ROUND0_BEGIN,
		self::ROUND0_END,*/
		self::ROUND1_BEGIN,
		self::ROUND1_END,
		self::ROUND2_BEGIN,
		self::ROUND2_END,
		self::ROUND3_BEGIN,
		self::ROUND3_END,
		self::ROUND4_BEGIN,
		self::ROUND4_END,
		self::GAME_REPORT,
		self::END_GAME
		];

	public static $N = [
		[
			'section'	=> /*T[*/'Client'/*]*/,
			'slug'		=> 'client',
			'fields'	=> [
			'buying_mode',
			'price_weight',
			'adv_budg_weight',
			'paym_term_weight',
			'delivery_weight',
			'features_weight',
			'warranty_weight'
			]
		],
		[
			'section'	=> /*T[*/'Products'/*]*/,
			'slug'		=> 'products',
			'fields'	=> [
			'max_price_accepted',
			'desired_price',
			'desired_quantity',
			'min_adv_budget_accepted',
			'desired_adv_budget',
			'min_paym_term_accepted',
			'desired_payment_term'
			]
		],
		[
			'section'	=> /*T[*/'Warranty'/*]*/,
			'slug'		=> 'warranty',
			'fields'	=> [
			'mandatory',
			'negotiable'
			]
		],
		[
			'section'	=> /*T[*/'Features'/*]*/,
			'slug'		=> 'features',
			'fields'	=> [
			'mandatory',
			'negotiable'
			]
		],
		[
			'section'	=> /*T[*/'Delivery Locations'/*]*/,
			'slug'		=> 'features',
			'fields'	=> [
			'max_delivery_time',
			'desired_delivery_time'
			]
		],
		[
			'section'	=> /*T[*/'Negotiation'/*]*/,
			'slug'		=> 'negotiation',
			'fields'	=> [
			'aggressiveness',
			'ask_for_features',
			'ask_for_warranty',
			'score_weight',
			'sweetener'
			]
		],
		];

	private $scenario;
	private $variables;
	private $scores;

	public function get ($key = null, $opts = null) {
		if (is_string ($key)) {
			$slug = self::slug ($key);
			switch ($key) {
				case 'owner_name':
					$user = new WP_User ($this->data['owner']);
					return $user->first_name . ' ' . $user->last_name;
				case 'round_name':
					return self::$S[$this->data['state']];
					break;
				case 'data':
					$data = unserialize ($this->data['state_data']);
					if (empty ($data)) return [];
					if (is_string ($opts) && isset ($data[$opts]))
						return $data[$opts];
					if ($opts == 'current')
						return $data[$this->data['state']];
					return $data;
					break;
				case 'market':
					$market = unserialize ($this->data['market']);
					if (empty ($market)) return [];
					return $market;
					break;
				case 'variables':
					if (!isset ($this->variables) || empty ($this->variables))
						$this->variables = empty ($this->data['variables']) ? [] : unserialize ($this->data['variables']);
					if (is_string ($opts)) {
						if (isset ($this->variables[$opts]))
							return $this->variables[$opts];
						return FALSE;
						}
					return $this->variables;
					break;
				case 'scores':
					if (!isset ($this->scores) || empty ($this->scores))
						$this->scores = empty ($this->data['scores']) ? [] : unserialize ($this->data['scores']);
					if (is_string ($opts)) {
						if (isset ($this->scores[$opts]))
							return $this->scores[$opts];
						return FALSE;
						}
					return $this->scores;
					break;
				}
			}
		return parent::get ($key, $opts);
		}

	public function set ($key = null, $value = null) {
		if (is_string ($key)) {
			$slug = self::slug ($key);
			switch ($key) {
				case 'data':
					$key = 'state_data';
					$value = serialize ($value);
					break;
				case 'market':
					$key = 'market';
					$value = serialize ($value);
					break;
				}
			}
		if (is_array ($key) && in_array ('data', $key)) {
			$key['state_data'] = serialize ($key['data']);
			unset ($key['data']);
			}
		if (is_array ($key) && in_array ('market', $key)) {
			$key['market'] = serialize ($key['market']);
			}
		return parent::set ($key, $value);
		}

	public function turn ($forward = TRUE) {
		if ($this->data['state'] == self::CANCELED_GAME)
			return FALSE;

		$prev = null;
		$next = null;
		reset (self::$SO);
		while (($state = current (self::$SO)) !== FALSE) {
			if ($state == $this->data['state']) {
				$prev = prev (self::$SO);
				if ($prev === FALSE) {
					$prev = null;
					reset (self::$SO);
					$next = next (self::$SO);
					}
				else {
					next (self::$SO);
					$next = next (self::$SO);
					if ($next === FALSE)
						$next = null;
					}
				break;
				}
			next (self::$SO);
			}
		reset (self::$SO);
		
		if ($forward) {
			if (is_null ($next)) return FALSE;
			$this->set ('state', $next);

			if ($next == self::ROUND4_END)
				$this->buy ();

			return TRUE;
			}
		else {
			if (is_null ($prev)) return FALSE;
			$this->set ('state', $prev);

			if ($prev == self::ROUND4_END) {
				$this->set ('ended', time ());
				$this->buy ();
				}

			return TRUE;
			}
		}

	public function buy () {
		if (!in_array ($this->data['state'], [self::ROUND4_END, self::GAME_REPORT]))
			return FALSE;

		$market = $this->scenario ('buying_mode') == 0 ? $this->compute ('buying_learning') : ($this->scenario ('buying_mode') == 1 ? $this->compute ('buying_competitive') : []);
		$this->set ('market', $market);
		return TRUE;
		}

	public function scenario ($key = null, $opts = null) {
		if (!($this->scenario instanceof FH_Scenario))
			$this->scenario = new FH_Scenario ($this->data['scenario']);
		return $this->scenario->get ($key, $opts);
		}

	public function compute ($key, $player = null) {
		$_quotations = [];
		$players = new FH_List ('FH_Player', [ sprintf ('game=%d', $this->ID) ]);
		$products = new FH_List ('FH_Product', $this->scenario ('path'));


		if (is_string ($key)) {
			switch ($key) {
				case 'buying_learning':
					if ($players->is ('empty')) return FALSE;
					$perceived_cost_all = [];
					$perceived_cost_sum = [];
					$perceived_cost_num = [];
					$perceived_cost_delta = [];
					$perceived_cost_abs_d = [];

					foreach ($players->get () as $player) {
						$perceived_cost = $player->compute ('perceived_cost');

						var_dump ($player->get ());
						var_dump ($perceived_cost);

						foreach ($perceived_cost as $product_slug => $product_value) {
							if (!isset ($perceived_cost_all[$product_slug]))
								$perceived_cost_all[$product_slug] = [];
							if (!isset ($perceived_cost_sum[$product_slug]))
								$perceived_cost_sum[$product_slug] = [];
							if (!isset ($perceived_cost_num[$product_slug]))
								$perceived_cost_num[$product_slug] = [];
							if (!isset ($perceived_cost_delta[$product_slug]))
								$perceived_cost_delta[$product_slug] = [];

							foreach ($product_value as $quality_slug => $quality_value) {
								if (!isset ($perceived_cost_all[$product_slug][$quality_slug]))
									$perceived_cost_all[$product_slug][$quality_slug] = [];
								$perceived_cost_all[$product_slug][$quality_slug][$player->get ()] = $quality_value;


								if (!isset ($perceived_cost_sum[$product_slug][$quality_slug]))
									$perceived_cost_sum[$product_slug][$quality_slug] = $quality_value;
								else
									$perceived_cost_sum[$product_slug][$quality_slug] += $quality_value;

								if (!isset ($perceived_cost_num[$product_slug][$quality_slug]))
									$perceived_cost_num[$product_slug][$quality_slug] = 1;
								else
									$perceived_cost_num[$product_slug][$quality_slug] ++;

								if (!isset ($perceived_cost_delta[$product_slug][$quality_slug]))
									$perceived_cost_delta[$product_slug][$quality_slug] = [];
								$perceived_cost_delta[$product_slug][$quality_slug][$player->get ()] = $quality_value;
								}
							}
						}

					echo "delta = ";
					var_dump ($perceived_cost_delta);

					if (!empty ($perceived_cost_all))
						foreach ($perceived_cost_all as $product_slug => $product_data) {
							if (!empty ($product_data))
								foreach ($product_data as $quality_slug => $quality_data) {
									asort ($perceived_cost_all[$product_slug][$quality_slug]);

									if (!empty ($perceived_cost_delta[$product_slug][$quality_slug]))
										foreach ($perceived_cost_delta[$product_slug][$quality_slug] as $player_slug => $player_data) {
											$perceived_cost_average = isset ($perceived_cost_sum[$product_slug][$quality_slug]) ? $perceived_cost_sum[$product_slug][$quality_slug] : 0;
											$perceived_cost_average = isset ($perceived_cost_num[$product_slug][$quality_slug]) && ($perceived_cost_num[$product_slug][$quality_slug] > 0) ? $perceived_cost_average / $perceived_cost_num[$product_slug][$quality_slug] : 0;
											$perceived_cost_delta[$product_slug][$quality_slug][$player_slug] = $player_data - $perceived_cost_average;
											$perceived_cost_abs_d[$product_slug][$quality_slug][$player_slug] = abs ($perceived_cost_delta[$product_slug][$quality_slug][$player_slug]);
											}
									}
							}

					if (!empty ($perceived_cost_abs_d))
						foreach ($perceived_cost_abs_d as $product_slug => $product_data) {
                                                        if (!empty ($product_data))
                                                                foreach ($product_data as $quality_slug => $quality_data) {
								$perceived_cost_abs_d[$product_slug][$quality_slug] = max ($quality_data);
								}
							}

					$players_array = $players->get ();
					$perceived_quantities = [];
					$basket = [];

					if (!empty ($perceived_cost_all))
						foreach ($perceived_cost_all as $product_slug => $product_data) {
							if (!empty ($product_data))
								foreach ($product_data as $quality_slug => $quality_data) {
									$reminder = 0;

									if (!empty ($quality_data))
										foreach ($quality_data as $player_slug => $player_perceived_cost) {
											$player = $players_array[$player_slug];
											if (!isset ($perceived_quantities[$player_slug]) && ($player instanceof FH_Player))
												$perceived_quantities[$player_slug] = $player->compute ('perceived_quantities');

											if (!isset ($basket[$player_slug]))
												$basket[$player_slug] = [];

											$amount_to_buy_from_this_player =
												$reminder +
													($perceived_quantities[$player_slug][$product_slug][$quality_slug][1] / $perceived_cost_num[$product_slug][$quality_slug]) *
													(1 - $perceived_cost_delta[$product_slug][$quality_slug][$player_slug]
													/ $perceived_cost_abs_d[$product_slug][$quality_slug]);
											
											$basket[$player_slug][$product_slug][$quality_slug] = min ([
												$perceived_quantities[$player_slug][$product_slug][$quality_slug][0],
												$amount_to_buy_from_this_player
												]);

											$reminder = $amount_to_buy_from_this_player - $basket[$player_slug][$product_slug][$quality_slug];
											}
									}
							}

					return $basket;
					break;
				case 'buying_competitive':
					if ($players->is ('empty')) return FALSE;
					$perceived_cost_array = [];
					$remaining_quantity = [];
					$offered_quantity = [];

					foreach ($products->get () as $product) {
						$qualities = $product->get ('quality');
						foreach ($qualities as $quality_slug => $quality_data)
							$remaining_quantity[$product->get ()][$quality_slug] = $quality_data['desired_quantity'];
						}

					foreach ($players->get () as $player) {
						$perceived_cost = $player->compute ('perceived_cost');
						
						foreach ($perceived_cost as $product_slug => $product_value) {
							if (!isset ($perceived_cost_array[$product_slug]))
								$perceived_cost_array[$product_slug] = [];
							foreach ($product_value as $quality_slug => $quality_value) {
								if (!isset ($perceived_cost_array[$product_slug][$quality_slug]))
									$perceived_cost_array[$product_slug][$quality_slug] = [];
								$perceived_cost_array[$product_slug][$quality_slug][$player->get ()] = $quality_value;
								}
							}

						$perceived_quantity = $player->compute ('perceived_quantity');
						foreach ($perceived_quantity as $product_slug => $product_quantity) {
							foreach ($product_quantity as $quality_slug => $quality_quantity) {
								$offered_quantity[$product_slug][$quality_slug][$player->get ()] = $quality_quantity;
								}
							}
						}

					$basket = [];
					foreach ($perceived_cost_array as $product_slug => $product_array) {
						foreach ($product_array as $quality_slug => $quality_array) {
							asort ($quality_array);
							$sorted_array = $quality_array;
							#$sorted_array = array_reverse ($quality_array);

							foreach ($sorted_array as $player_id => $player_pv) {
								if ($remaining_quantity[$product_slug][$quality_slug] > 0) {
									$basket[$player_id][$product_slug][$quality_slug] = min ([
										$offered_quantity[$product_slug][$quality_slug][$player_id],
										$remaining_quantity[$product_slug][$quality_slug]
										]);
									$remaining_quantity[$product_slug][$quality_slug] -= $basket[$player_id][$product_slug][$quality_slug];
									}
								else
									$basket[$player_id][$product_slug][$quality_slug] = 0;
								}
							}
						}

					return $basket;
					break;
				}
			}
		}
	}
?>
