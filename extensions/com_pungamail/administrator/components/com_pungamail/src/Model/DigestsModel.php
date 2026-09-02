<?php
/** @package Punga.Mail @subpackage Administrator.Model */
namespace Punga\Component\PungaMail\Administrator\Model;
use Joomla\CMS\MVC\Model\ListModel; use Joomla\Database\ParameterType;
/** Joomla-standard digest automation list. */
final class DigestsModel extends ListModel
{
	/** @param array<string,mixed> $config Model configuration. */
	public function __construct($config = []) { $config['filter_fields'] ??= ['id','a.id','title','a.title','state','a.state','next_run_at','a.next_run_at','generation_mode','a.generation_mode']; parent::__construct($config); }
	/** @return void */ protected function populateState($ordering = 'a.next_run_at', $direction = 'ASC'): void { parent::populateState($ordering, $direction); }
	/** @return \Joomla\Database\DatabaseQuery */ protected function getListQuery()
	{
		$db = $this->getDatabase(); $query = $db->getQuery(true)->select(['a.*','t.title AS template_title'])->from($db->quoteName('#__pungamail_digests','a'))->leftJoin($db->quoteName('#__pungamail_templates','t').' ON t.id = a.template_id'); $search = trim((string) $this->getState('filter.search'));
		if ($search !== '') { $like = '%'.str_replace(' ','%',$search).'%'; $query->where($db->quoteName('a.title').' LIKE :search')->bind(':search',$like); }
		$state = (string) $this->getState('filter.state'); if ($state === '') { $trashed=-2; $query->where($db->quoteName('a.state').' <> :trashed')->bind(':trashed',$trashed,ParameterType::INTEGER); } else { $value=(int)$state; $query->where($db->quoteName('a.state').' = :state')->bind(':state',$value,ParameterType::INTEGER); }
		$order=(string)$this->getState('list.ordering','a.next_run_at'); $direction=strtoupper((string)$this->getState('list.direction','ASC'))==='DESC'?'DESC':'ASC'; $query->order($db->escape($order).' '.$direction); return $query;
	}
}
