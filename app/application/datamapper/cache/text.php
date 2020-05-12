<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
$cache = array (
  'table' => 'koken_text',
  'fields' => 
  array (
    0 => 'id',
    1 => 'title',
    2 => 'draft_title',
    3 => 'slug',
    4 => 'featured_image_id',
    5 => 'featured',
    6 => 'featured_on',
    7 => 'featured_order',
    8 => 'custom_featured_image',
    9 => 'content',
    10 => 'draft',
    11 => 'excerpt',
    12 => 'published',
    13 => 'page_type',
    14 => 'published_on',
    15 => 'created_on',
    16 => 'modified_on',
    17 => 'tags',
    18 => 'internal_id',
  ),
  'validation' => 
  array (
    'internal_id' => 
    array (
      'label' => 'Internal id',
      'rules' => 
      array (
        0 => 'internalize',
        1 => 'required',
      ),
      'field' => 'internal_id',
    ),
    'page_type' => 
    array (
      'rules' => 
      array (
        0 => 'validate_type',
      ),
      'field' => 'page_type',
    ),
    'slug' => 
    array (
      'rules' => 
      array (
        0 => 'slug',
        1 => 'required',
      ),
      'field' => 'slug',
    ),
    'created_on' => 
    array (
      'rules' => 
      array (
        0 => 'validate_created_on',
      ),
      'field' => 'created_on',
    ),
    'tags' => 
    array (
      'rules' => 
      array (
        0 => 'format_tags',
      ),
      'field' => 'tags',
    ),
    'title' => 
    array (
      'rules' => 
      array (
        0 => 'format_title',
      ),
      'get_rules' => 
      array (
        0 => 'readify',
      ),
      'field' => 'title',
    ),
    'draft_title' => 
    array (
      'rules' => 
      array (
        0 => 'format_title',
      ),
      'field' => 'draft_title',
    ),
    'draft' => 
    array (
      'rules' => 
      array (
        0 => 'format_content',
      ),
      'field' => 'draft',
    ),
    'content' => 
    array (
      'rules' => 
      array (
        0 => 'format_content',
      ),
      'field' => 'content',
    ),
    'published' => 
    array (
      'rules' => 
      array (
        0 => 're_slug',
      ),
      'field' => 'published',
    ),
    'id' => 
    array (
      'field' => 'id',
      'rules' => 
      array (
        0 => 'integer',
      ),
    ),
    'featured_image_id' => 
    array (
      'field' => 'featured_image_id',
      'rules' => 
      array (
      ),
    ),
    'featured' => 
    array (
      'field' => 'featured',
      'rules' => 
      array (
      ),
    ),
    'featured_on' => 
    array (
      'field' => 'featured_on',
      'rules' => 
      array (
      ),
    ),
    'featured_order' => 
    array (
      'field' => 'featured_order',
      'rules' => 
      array (
      ),
    ),
    'custom_featured_image' => 
    array (
      'field' => 'custom_featured_image',
      'rules' => 
      array (
      ),
    ),
    'excerpt' => 
    array (
      'field' => 'excerpt',
      'rules' => 
      array (
      ),
    ),
    'published_on' => 
    array (
      'field' => 'published_on',
      'rules' => 
      array (
      ),
    ),
    'modified_on' => 
    array (
      'field' => 'modified_on',
      'rules' => 
      array (
      ),
    ),
    'featured_image' => 
    array (
      'field' => 'featured_image',
      'rules' => 
      array (
      ),
    ),
    'category' => 
    array (
      'field' => 'category',
      'rules' => 
      array (
      ),
    ),
    'album' => 
    array (
      'field' => 'album',
      'rules' => 
      array (
      ),
    ),
  ),
  'has_one' => 
  array (
    'featured_image' => 
    array (
      'class' => 'content',
      'auto_populate' => true,
      'other_field' => 'text',
      'join_self_as' => 'text',
      'join_other_as' => 'featured_image',
      'join_table' => '',
      'reciprocal' => false,
      'cascade_delete' => true,
    ),
  ),
  'has_many' => 
  array (
    'category' => 
    array (
      'auto_populate' => true,
      'class' => 'category',
      'other_field' => 'text',
      'join_self_as' => 'text',
      'join_other_as' => 'category',
      'join_table' => '',
      'reciprocal' => false,
      'cascade_delete' => true,
    ),
    'album' => 
    array (
      'auto_populate' => true,
      'order_by' => 'title',
      'class' => 'album',
      'other_field' => 'text',
      'join_self_as' => 'text',
      'join_other_as' => 'album',
      'join_table' => '',
      'reciprocal' => false,
      'cascade_delete' => true,
    ),
  ),
  '_field_tracking' => 
  array (
    'get_rules' => 
    array (
      0 => 'title',
    ),
    'matches' => 
    array (
    ),
    'intval' => 
    array (
      0 => 'id',
    ),
  ),
);