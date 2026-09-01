<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Newsletter\HtmlView $this */

defined('_JEXEC') or die;

use DateTimeZone;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

$item = $this->item;
$isDraft = $item === null || (int) $item->status === NewsletterRepository::STATUS_DRAFT;
$selectedById = [];
foreach ($this->selectedItems as $selected)
{
	$selectedById[(int) $selected->content_id] = $selected;
}
$articlesById = [];
foreach ($this->availableArticles as $article)
{
	$articlesById[(int) $article->id] = $article;
}
foreach ($this->selectedItems as $selected)
{
	if (!isset($articlesById[(int) $selected->content_id]))
	{
		$articlesById[(int) $selected->content_id] = (object) [
			'id' => (int) $selected->content_id,
			'title' => (string) ($selected->article_title ?? Text::_('COM_PUNGAMAIL_ARTICLE_UNAVAILABLE')),
			'introtext' => (string) ($selected->article_introtext ?? ''),
			'category_title' => '',
			'publish_up' => null,
			'created' => null,
		];
	}
}

$cutoffDateValue = '';
$cutoffDisplay = Text::_('COM_PUNGAMAIL_BEGINNING_OF_CONTENT');
if ($this->contentCutoffStart)
{
	$cutoff = Factory::getDate($this->contentCutoffStart, 'UTC');
	$cutoff->setTimezone(new DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC')));
	$cutoffDateValue = $cutoff->format('Y-m-d');
	$cutoffDisplay = $cutoff->format(Text::_('DATE_FORMAT_LC4'));
}
?>
<div class="container-fluid">
<?php if (!$isDraft) : ?>
	<div class="alert alert-info"><?php echo Text::_('COM_PUNGAMAIL_IMMUTABLE_HELP'); ?></div>
	<div class="card mb-3"><div class="card-body">
		<h3><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></h3>
		<p><strong><?php echo Text::_('COM_PUNGAMAIL_SUBJECT'); ?>:</strong> <?php echo htmlspecialchars((string) $item->snapshot_subject, ENT_QUOTES, 'UTF-8'); ?></p>
		<p><strong><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?>:</strong> <?php echo (int) $item->recipient_count; ?> &nbsp; <strong><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?>:</strong> <?php echo (int) $item->sent_count; ?> &nbsp; <strong><?php echo Text::_('COM_PUNGAMAIL_FAILED'); ?>:</strong> <?php echo (int) $item->failed_count; ?></p>
	</div></div>
	<?php if ($item->snapshot_html) : ?>
		<iframe title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SENT_NEWSLETTER'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:700px;border:1px solid #ccc;background:#fff" srcdoc="<?php echo htmlspecialchars((string) $item->snapshot_html, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
	<?php endif; ?>
	<div class="card mt-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_FROZEN_RECIPIENTS'); ?></strong></div><div class="card-body p-0">
		<table class="table table-striped mb-0">
			<thead><tr><th><?php echo Text::_('COM_PUNGAMAIL_EMAIL'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SOURCE'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_ATTEMPTS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_ERROR'); ?></th></tr></thead>
			<tbody>
			<?php foreach ($this->queueRecipients as $recipient) : ?>
				<tr>
					<td><code><?php echo htmlspecialchars((string) $recipient->email, ENT_QUOTES, 'UTF-8'); ?></code></td>
					<td><?php echo htmlspecialchars((string) $recipient->source, ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo htmlspecialchars((string) $recipient->status, ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo (int) $recipient->attempts; ?></td>
					<td><?php echo $recipient->sent_at ? HTMLHelper::_('date', $recipient->sent_at, Text::_('DATE_FORMAT_LC5'), 'UTC') : '—'; ?></td>
					<td class="small text-danger"><?php echo htmlspecialchars((string) ($recipient->last_error ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div></div>
	<form class="mt-3" action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.duplicate'); ?>" method="post">
		<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
		<button class="btn btn-primary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_DUPLICATE_AS_DRAFT'); ?></button>
		<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>"><?php echo Text::_('JTOOLBAR_BACK'); ?></a>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php else : ?>
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post">
		<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>">
		<div class="row g-3">
			<div class="col-12 col-xl-8">
				<div class="card mb-3"><div class="card-body">
					<div class="mb-3">
						<label class="form-label" for="pm-title"><?php echo Text::_('COM_PUNGAMAIL_INTERNAL_TITLE'); ?></label>
						<input class="form-control" id="pm-title" name="title" required value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div class="mb-3">
						<label class="form-label" for="pm-subject"><?php echo Text::_('COM_PUNGAMAIL_EMAIL_SUBJECT'); ?></label>
						<input class="form-control" id="pm-subject" name="subject" required value="<?php echo htmlspecialchars((string) ($item->subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div>
						<label class="form-label" for="pm-body"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_BODY_MARKDOWN'); ?></label>
						<textarea class="form-control font-monospace" id="pm-body" name="body_markdown" rows="16"><?php echo htmlspecialchars((string) ($item->body_markdown ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_HELP'); ?></div>
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_PLACEHOLDER_HELP'); ?></div>
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_IMAGE_HELP'); ?></div>
					</div>
				</div></div>

				<div class="card mb-3">
					<div class="card-header"><strong><?php echo Text::sprintf('COM_PUNGAMAIL_NEW_CONTENT_SINCE', $cutoffDisplay); ?></strong></div>
					<div class="card-body border-bottom">
						<div class="row g-2 align-items-end">
							<div class="col-sm-6 col-md-5">
								<label class="form-label" for="pm-content-cutoff"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_PUBLISHED_SINCE'); ?></label>
								<input class="form-control" id="pm-content-cutoff" type="date" name="content_cutoff_start" value="<?php echo htmlspecialchars($cutoffDateValue, ENT_QUOTES, 'UTF-8'); ?>">
							</div>
							<div class="col-auto">
								<button class="btn btn-outline-secondary" type="submit" name="task" value="newsletter.applyCutoff"><?php echo Text::_('COM_PUNGAMAIL_APPLY_DATE'); ?></button>
							</div>
						</div>
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_DATE_HELP'); ?></div>
					</div>
					<div class="card-body p-0">
					<?php if ($articlesById === []) : ?>
						<p class="text-muted m-3"><?php echo Text::_('COM_PUNGAMAIL_NO_NEW_ARTICLES'); ?></p>
					<?php else : ?>
						<?php $defaultOrder = 0; foreach ($articlesById as $contentId => $article) : $selected = $selectedById[$contentId] ?? null; ?>
							<div class="border-bottom p-3">
								<div class="form-check mb-2">
									<input class="form-check-input" type="checkbox" name="selected_articles[]" value="<?php echo $contentId; ?>" id="article-<?php echo $contentId; ?>" <?php echo $selected ? 'checked' : ''; ?>>
									<label class="form-check-label fw-semibold" for="article-<?php echo $contentId; ?>"><?php echo htmlspecialchars((string) $article->title, ENT_QUOTES, 'UTF-8'); ?></label>
								</div>
								<div class="small text-muted mb-2"><?php echo htmlspecialchars((string) ($article->category_title ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($article->publish_up ?? $article->created ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
								<div class="row g-2">
									<div class="col-md-2"><label class="form-label small"><?php echo Text::_('JGRID_HEADING_ORDERING'); ?></label><input type="number" class="form-control form-control-sm" name="article_ordering[<?php echo $contentId; ?>]" value="<?php echo (int) ($selected->ordering ?? $defaultOrder++); ?>"></div>
									<div class="col-md-10"><label class="form-label small"><?php echo Text::_('COM_PUNGAMAIL_TITLE_OVERRIDE'); ?></label><input class="form-control form-control-sm" name="title_override[<?php echo $contentId; ?>]" value="<?php echo htmlspecialchars((string) ($selected->title_override ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_TITLE_OVERRIDE_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>"></div>
									<div class="col-12"><label class="form-label small"><?php echo Text::_('COM_PUNGAMAIL_EXCERPT_OVERRIDE'); ?></label><textarea class="form-control form-control-sm" rows="2" name="excerpt_override[<?php echo $contentId; ?>]" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_EXCERPT_OVERRIDE_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($selected->excerpt_override ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="col-12 col-xl-4">
				<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?></strong></div><div class="card-body">
					<div class="form-check mb-3">
						<input type="hidden" name="include_subscribers" value="0">
						<input class="form-check-input" type="checkbox" name="include_subscribers" value="1" id="include-subscribers" <?php echo $item === null || (int) $item->include_subscribers === 1 ? 'checked' : ''; ?>>
						<label class="form-check-label" for="include-subscribers"><?php echo Text::_('COM_PUNGAMAIL_ALL_CONFIRMED_SUBSCRIBERS'); ?></label>
					</div>
					<div class="fw-semibold mb-2"><?php echo Text::_('COM_PUNGAMAIL_ADDITIONAL_USER_GROUPS'); ?></div>
					<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_GROUPS_RESPECT_OPT_OUT'); ?></div>
					<?php foreach ($this->userGroups as $group) : ?>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="group_ids[]" value="<?php echo (int) $group->id; ?>" id="group-<?php echo (int) $group->id; ?>" <?php echo in_array((int) $group->id, $this->selectedGroupIds, true) ? 'checked' : ''; ?>>
							<label class="form-check-label" for="group-<?php echo (int) $group->id; ?>"><?php echo htmlspecialchars((string) $group->title, ENT_QUOTES, 'UTF-8'); ?></label>
						</div>
					<?php endforeach; ?>
				</div></div>
				<div class="card"><div class="card-body">
					<div class="d-grid gap-2">
						<button class="btn btn-primary" type="submit" name="task" value="newsletter.save"><?php echo Text::_('COM_PUNGAMAIL_SAVE_DRAFT'); ?></button>
						<button class="btn btn-outline-primary" type="submit" name="task" value="newsletter.preview"><?php echo Text::_('COM_PUNGAMAIL_PREVIEW'); ?></button>
						<button class="btn btn-outline-secondary" type="submit" name="task" value="newsletter.sendTest"><?php echo Text::_('COM_PUNGAMAIL_SAVE_SEND_TEST'); ?></button>
						<button class="btn btn-success" type="submit" name="task" value="newsletter.preflight"><?php echo Text::_('COM_PUNGAMAIL_REVIEW_AND_SEND'); ?></button>
						<a class="btn btn-link" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>"><?php echo Text::_('JCANCEL'); ?></a>
					</div>
				</div></div>
			</div>
		</div>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php endif; ?>
</div>
