<?php

class Text extends DataMapper {

	var $table = 'text';

	var $has_one = array(
		'featured_image' => array(
			'class' => 'content',
			'auto_populate' => true
		),
	);

	var $has_many = array(
		'category' => array(
			'auto_populate' => true
		),
		'album' => array(
			'auto_populate' => true,
			'order_by' => 'title'
		)
	);

	var $validation = array(
		'internal_id' => array(
			'label' => 'Internal id',
			'rules' => array('internalize', 'required')
		),
		'page_type' => array(
			'rules' => array('validate_type')
		),
		'slug' => array(
			'rules' => array('slug', 'required')
		),
		'created_on' => array(
			'rules' => array('validate_created_on')
		),
		'tags' => array(
			'rules' => array('format_tags')
		),
		'title' => array(
			'rules' => array('format_title'),
			'get_rules' => array('readify')
		),
		'draft_title' => array(
			'rules' => array('format_title')
		),
		'draft' => array(
			'rules' => array('format_content')
		),
		'content' => array(
			'rules' => array('format_content')
		),
		'published' => array(
			'rules' => array('re_slug')
		)
 	);

	function _format_content($field)
	{
		$this->{$field} = rawurldecode(trim($this->{$field}));
		if ($field === 'content')
		{
			$this->draft = $this->content;
		}
		return true;
	}

	function _format_title($field)
	{
		$this->{$field} = rawurldecode(trim($this->{$field}));
		if ($field === 'title')
		{
			$this->draft_title = $this->title;
		}
		return true;
	}

	function _validate_type()
	{
		$values = array('essay', 'page');
		if (in_array($this->page_type, $values))
		{
			$this->page_type = array_search($this->page_type, $values);
		}
		else
		{
			return false;
		}
	}

	function _validate_created_on()
	{
		$val = $this->created_on;
		if (is_numeric($val) && strlen($val) <= 10)
		{
			return true;
		}
		return false;
	}

	/**
	 * Create internal ID if one is not present
	 */
	function _internalize($field)
	{
		$this->{$field} = koken_rand();
	}

	function _re_slug($field)
	{
		if ($this->published > 0)
		{
			$this->_slug('slug');
		}
	}

	function _slug($field)
	{
		$this->load->helper(array('url', 'text', 'string'));
		$slug = reduce_multiples(
					strtolower(
						url_title(
							convert_accented_characters($this->title), 'dash'
						)
					)
				, '-', true);

		if (empty($slug))
		{
			$t = new Text;
			$max = $t->select_max('id')->get();
			$slug = $max->id + 1;
		}

		if (is_numeric($slug))
		{
			$slug = "$slug-1";
		}

		if ($this->slug === $slug || !empty($this->slug))
		{
			return;
		}

		$s = new Slug;

		// Need to lock the table here to ensure that requests arriving at the same time
		// still get unique slugs
		if ($this->has_db_permission('lock tables'))
		{
			$this->db->query("LOCK TABLE {$s->table} WRITE");
			$locked = true;
		}
		else
		{
			$locked = false;
		}

		$page_type = is_numeric($this->page_type) ? $this->page_type : 0;
		$prefix = $page_type === 1 ? 'page' : 'essay';

		while($s->where('id', "$prefix.$slug")->count() > 0)
		{
			$slug = increment_string($slug, '-');
		}

		$this->db->query("INSERT INTO {$s->table}(id) VALUES ('$prefix.$slug')");

		if ($locked)
		{
			$this->db->query('UNLOCK TABLES');
		}

		$this->slug = $slug;
	}

	/**
	 * Constructor: calls parent constructor
	 */
    function __construct($id = NULL)
	{
		parent::__construct($id);
    }

	function _readify()
	{
		if (isset($this->tags) && !empty($this->tags))
		{
			$this->tags = explode(',', trim($this->tags, ','));
		}
		else
		{
			$this->tags = array();
		}
	}

	function _format_tags()
	{
		if (empty($this->tags))
		{
			$this->tags = null;

			if (isset($this->old_tags))
			{
				$t = new Tag();
				$t->manage(array(), $this->old_tags, 'text');
			}
		}
		else
		{
			list($this->tags, $new) = koken_format_tags($this->tags);

			if (isset($this->old_tags) && $this->old_published != $this->published)
			{
				if ($this->old_published == $this->published)
				{
					$old = $this->old_tags;
					$add = array_diff($new, $old);
					$remove = array_diff($old, $new);
				}
				else
				{
					if ($this->published)
					{
						$add = $new;
					}
					else
					{
						$add = array();
						$remove = $this->old_tags;
					}
				}
			}
			else if ($this->published)
			{
				$add = $new;
				$remove = false;
			}
			else
			{
				return true;
			}

			$t = new Tag();
			$t->manage($add, $remove, 'text');
		}
	}

	function listing($params)
	{
		$options = array(
			'page' => 1,
			'order_by' => 'modified_on',
			'order_direction' => 'DESC',
			'tags' => false,
			'tags_not' => false,
			'match_all_tags' => false,
			'limit' => 100,
			'published' => 1,
			'category' => false,
			'category_not' => false,
			'featured' => null,
			'type' => false,
			'state' => false,
			'year' => false,
			'year_not' => false,
			'letter' => false,
			'month' => false,
			'month_not' => false,
			'day' => false,
			'day_not' => false,
			'render' => true,
			'tags' => false
		);
		$options = array_merge($options, $params);

		if (!$options['auth'])
		{
			$options['state'] = 'published';
		}

		if ($options['featured'] == 1 && !isset($params['order_by']))
		{
			$options['order_by'] = 'featured_on';
		}

		if (is_numeric($options['limit']) && $options['limit'] > 0)
		{
			$options['limit'] = min($options['limit'], 100);
		}
		else
		{
			$options['limit'] = 100;
		}
		if ($options['type'])
		{
			if ($options['type'] === 'essay')
			{
				$this->where('page_type', 0);
			}
			else if ($options['type'] === 'page')
			{
				$this->where('page_type', 1);
			}
		}
		if ($options['state'])
		{
			if ($options['state'] === 'published')
			{
				$this->where('published', 1);
			}
			else if ($options['state'] === 'draft' && $options['order_by'] !== 'published_on' )
			{
				$this->where('published', 0);
			}
		}

		if ($options['order_by'] === 'published_on')
		{
			$this->where('published', 1);
		}

		if ($options['tags'] || $options['tags_not'])
		{
			$this->group_start();
			if ($options['match_all_tags'])
			{
				$method = 'like';
			}
			else
			{
				$method = 'or_like';
			}

			if ($options['tags_not'])
			{
				$method = str_replace('like', 'not_like', $method);
				$options['tags'] = $options['tags_not'];
			}
			$tags = explode(',', urldecode($options['tags']));
			foreach($tags as $t)
			{
				$this->{$method}('tags', ',' . $t . ',', 'both');
			}
			$this->group_end();
		}

		if (!is_null($options['featured']))
		{
			$this->where('featured', $options['featured']);
			if ($options['featured'])
			{
				$this->where('published', 1);
			}
		}
		if ($options['category'])
		{
			$this->where_related('category', 'id', $options['category']);
		}
		else if ($options['category_not'])
		{
			$cat = new Text;
			$cat->select('id')->where_related('category', 'id', $options['category_not'])->get_iterated();
			$cids = array();
			foreach($cat as $c)
			{
				$cids[] = $c->id;
			}
			$this->where_not_in('id', $cids);
		}

		if ($options['order_by'] === 'created_on' || $options['order_by'] === 'published_on' || $options['order_by'] === 'modified_on')
		{
			$bounds_order = $options['order_by'];
		}
		else
		{
			$bounds_order = 'published_on';
		}

		$s = new Setting;
		$s->where('name', 'site_timezone')->get();
		$tz = new DateTimeZone($s->value);
		$offset = $tz->getOffset( new DateTime('now', new DateTimeZone('UTC')) );

		if ($offset === 0)
		{
			$shift = '';
		}
		else
		{
			$shift = ($offset < 0 ? '-' : '+') . abs($offset);
		}

		// Do this before date filters are applied, and only if sorted by created_on
		$bounds = $this->get_clone()
					->select('COUNT(*) as count, MONTH(FROM_UNIXTIME(' . $bounds_order . $shift . ')) as month, YEAR(FROM_UNIXTIME(' . $bounds_order . $shift . ')) as year')
					->group_by('month,year')
					->order_by('year')
					->get_iterated();

		$dates = array();
		foreach($bounds as $b)
		{
			if (!isset($dates[$b->year])) {
				$dates[$b->year] = array();
			}

			$dates[$b->year][$b->month] = (int) $b->count;
		}

		if (in_array($options['order_by'], array('created_on', 'published_on', 'modified_on')))
		{
			$date_col = $options['order_by'];
		}
		else
		{
			$date_col = 'published_on';
		}

		if ($options['year'] || $options['year_not'])
		{
			if ($options['year_not'])
			{
				$options['year'] = $options['year_not'];
				$compare = ' !=';
			}
			else
			{
				$compare = '';
			}
			$this->where('YEAR(FROM_UNIXTIME(' . $date_col . $shift . '))' . $compare, $options['year']);
		}
		if ($options['month'] || $options['month_not'])
		{
			if ($options['month_not'])
			{
				$options['month'] = $options['month_not'];
				$compare = ' !=';
			}
			else
			{
				$compare = '';
			}
			$this->where('MONTH(FROM_UNIXTIME(' . $date_col . $shift . '))' . $compare, $options['month']);
		}
		if ($options['day'] || $options['day_not'])
		{
			if ($options['day_not'])
			{
				$options['day'] = $options['day_not'];
				$compare = ' !=';
			}
			else
			{
				$compare = '';
			}
			$this->where('DAY(FROM_UNIXTIME(' . $date_col . $shift . '))' . $compare, $options['day']);
		}

		if ($options['letter'])
		{
			if ($options['letter'] === 'num')
			{
				$this->where('title <', 'a');
			} else {
				$this->like('title', $options['letter'], 'after');
			}

		}

		$final = $this->paginate($options);

		$final['dates'] = $dates;

		$data = $this->order_by($options['order_by'] .' ' . $options['order_direction'] . ', id ' . $options['order_direction'])->get_iterated();

		if (!$options['limit'])
		{
			$final['per_page'] = $data->result_count();
			$final['total'] = $data->result_count();
		}

		$final['counts'] = array( 'total' => $final['total'] );

		$final['text'] = array();
		foreach($data as $page)
		{
			$final['text'][] = $page->to_array($options);
		}
		return $final;
	}

	function to_array($options = array())
	{
		$options = array_merge( array('auth' => false, 'render' => true), $options );

		$koken_url_info = $this->config->item('koken_url_info');

		$exclude = array('deleted', 'total_count', 'video_count', 'audio_count', 'featured_image_id', 'custom_featured_image');
		$dates = array('created_on', 'modified_on', 'published_on', 'featured_on');
		$strings = array('title', 'content', 'excerpt');
		$bools = array('published', 'featured');

		if (!$this->published)
		{
			$this->published_on = time();
		}
		list($data, $public_fields) = $this->prepare_for_output($options, $exclude, $bools, $dates, $strings);

		if (strlen(trim($data['draft'])) === 0)
		{
			$data['draft'] = $data['content'];
		}

		if (strlen(trim($data['draft_title'])) === 0)
		{
			$data['draft_title'] = $data['title'];
		}

		if (!$data['featured'])
		{
			unset($data['featured_on']);
		}

		if ($data['page_type'] != 0)
		{
			unset($data['featured']);
			unset($data['featured_on']);
		}

		if (!$options['auth'])
		{
			unset($data['internal_id']);
			unset($data['draft']);
			unset($data['draft_title']);
		}

		if (array_key_exists('page_type', $data))
		{
			switch($data['page_type'])
			{
				case 1:
					$data['page_type'] = 'page';
					break;
				default:
					$data['page_type'] = 'essay';
			}
		}

		$data['__koken__'] = $data['page_type'];

		$data['categories'] = array();
		foreach($this->categories as $category)
		{
			if ($data['page_type'] === 'essay')
			{
				$data['categories'][] = array_merge($category->to_array(array('limit_to' => 'essay')), array('__koken__' => 'category_essays'));
			}
			else
			{
				$data['categories'][] = $category->to_array();
			}
		}

		$data['topics'] = array();
		foreach($this->albums as $a)
		{
			$data['topics'][] = $a->to_array(array('with_topics' => false));
		}

		if ($this->featured_image->id && $this->featured_image->deleted == 0)
		{
			$data['featured_image'] = $this->featured_image->to_array();
		}
		else if (!empty($this->custom_featured_image))
		{
			$c = new Content;
			$data['featured_image'] = $c->to_array_custom($this->custom_featured_image);
		}
		else
		{
			$data['featured_image'] = false;
		}

		$rendered = Shutter::shortcodes( $data['content'], array( $this, $options ) );

		if (empty($options) || ( isset($options['render']) && $options['render'] ))
		{
			$data['content'] = $rendered;
			if (isset($data['draft']))
			{
				$data['draft'] = Shutter::shortcodes( $data['draft'], array( $this, $options ) );
			}
		}

		if (empty($data['excerpt']))
		{
			$rendered = preg_replace('/<figure class="k-content-embed">.*?<\/figure>/msU', '', $rendered);
			$clean_parts = explode( ' ', preg_replace('/([\.\?\!]+)([^\s]\s*[a-z][a-z\s]*)/', '$1 $2', trim( strip_tags( preg_replace('/\{\{.*\}\}/', '', $rendered) ) ) ) );
			$excerpt = '';
			while(count($clean_parts) && ($next = array_shift($clean_parts)) && strlen(trim($excerpt) . ' ' . trim($next)) <= 254)
			{
				$excerpt .= ' ' . trim($next);
			}
			$data['excerpt'] = trim($excerpt);
			if (count($clean_parts))
			{
				$data['excerpt'] = preg_replace('/[^\w]$/u', '', $data['excerpt']) . '…';
			}
		}
		if (isset($options['order_by']) && in_array($options['order_by'], array( 'created_on', 'modified_on', 'published_on' )))
		{
			$data['date'] =& $data[ $options['order_by'] ];
		}
		else if ($data['page_type'] === 'essay')
		{
			$data['date'] =& $data['published_on'];
		}

		$cat = isset($options['category']) ? $options['category'] : (isset($options['context']) && strpos($options['context'], 'category-') === 0 ? str_replace('category-', '', $options['context']) : false);

		if ($cat)
		{
			if (is_numeric($cat))
			{
				foreach($data['categories'] as $c)
				{
					if ($c['id'] == $cat)
					{
						$cat = $c['slug'];
						break;
					}
				}
			}
		}

		$data['url'] = $this->url(array(
				'date' => $data['published_on'],
				'tag' => isset($options['tags']) ? $options['tags'] : (isset($options['context']) && strpos($options['context'], 'tag-') === 0 ? str_replace('tag-', '', $options['context']) : false),
				'category' => $cat,
			)
		);

		if ($data['url'])
		{
			list($data['__koken_url'], $data['url']) = $data['url'];
			list(,$data['canonical_url']) = $this->url(array(
				'date' => $data['published_on']
			));
		}

		return Shutter::filter('api.text', array( $data, $this, $options ));

	}
}

/* End of file page.php */
/* Location: ./application/models/page.php */