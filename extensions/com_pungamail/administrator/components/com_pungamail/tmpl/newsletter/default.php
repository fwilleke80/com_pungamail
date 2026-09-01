<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Newsletter\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
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
			'title' => (string) ($selected->article_title ?? '[Article unavailable]'),
			'introtext' => (string) ($selected->article_introtext ?? ''),
			'category_title' => '',
			'publish_up' => null,
			'created' => null,
		];
	}
}
?>
<div class="container-fluid">
<?php if (!$isDraft) : ?>
	<div class="alert alert-info">This newsletter has been queued or sent and is immutable. Duplicate it to make changes.</div>
	<div class="card mb-3"><div class="card-body">
		<h3><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></h3>
		<p><strong>Subject:</strong> <?php echo htmlspecialchars((string) $item->snapshot_subject, ENT_QUOTES, 'UTF-8'); ?></p>
		<p><strong>Recipients:</strong> <?php echo (int) $item->recipient_count; ?> &nbsp; <strong>Sent:</strong> <?php echo (int) $item->sent_count; ?> &nbsp; <strong>Failed:</strong> <?php echo (int) $item->failed_count; ?></p>
	</div></div>
	<?php if ($item->snapshot_html) : ?>
		<iframe title="Sent newsletter" style="width:100%;height:700px;border:1px solid #ccc;background:#fff" srcdoc="<?php echo htmlspecialchars((string) $item->snapshot_html, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
	<?php endif; ?>
	<div class="card mt-3"><div class="card-header"><strong>Frozen recipients</strong></div><div class="card-body p-0">
		<table class="table table-striped mb-0">
			<thead><tr><th>Email</th><th>Source</th><th>Status</th><th>Attempts</th><th>Sent</th><th>Error</th></tr></thead>
			<tbody>
			<?php foreach ($this->queueRecipients as $recipient) : ?>
				<tr>
					<td><code><?php echo htmlspecialchars((string) $recipient->email, ENT_QUOTES, 'UTF-8'); ?></code></td>
					<td><?php echo htmlspecialchars((string) $recipient->source, ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo htmlspecialchars((string) $recipient->status, ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo (int) $recipient->attempts; ?></td>
					<td><?php echo $recipient->sent_at ? htmlspecialchars((string) $recipient->sent_at, ENT_QUOTES, 'UTF-8') . ' UTC' : '—'; ?></td>
					<td class="small text-danger"><?php echo htmlspecialchars((string) ($recipient->last_error ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div></div>
	<form class="mt-3" action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.duplicate'); ?>" method="post">
		<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
		<button class="btn btn-primary" type="submit">Duplicate as new draft</button>
		<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>">Back</a>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php else : ?>
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post">
		<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>">
		<div class="row g-3">
			<div class="col-12 col-xl-8">
				<div class="card mb-3"><div class="card-body">
					<div class="mb-3">
						<label class="form-label" for="pm-title">Internal title</label>
						<input class="form-control" id="pm-title" name="title" required value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div class="mb-3">
						<label class="form-label" for="pm-subject">Email subject</label>
						<input class="form-control" id="pm-subject" name="subject" required value="<?php echo htmlspecialchars((string) ($item->subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div>
						<label class="form-label" for="pm-body">Newsletter introduction (Markdown)</label>
						<textarea class="form-control font-monospace" id="pm-body" name="body_markdown" rows="14"><?php echo htmlspecialchars((string) ($item->body_markdown ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
						<div class="form-text">Supported: headings, paragraphs, bold, italic, links and ordered/unordered lists. Raw HTML is escaped.</div>
					</div>
				</div></div>

				<div class="card mb-3"><div class="card-header"><strong>New content since the previous newsletter</strong></div><div class="card-body p-0">
				<?php if ($articlesById === []) : ?>
					<p class="text-muted m-3">No newly published articles found.</p>
				<?php else : ?>
					<?php $defaultOrder = 0; foreach ($articlesById as $contentId => $article) : $selected = $selectedById[$contentId] ?? null; ?>
						<div class="border-bottom p-3">
							<div class="form-check mb-2">
								<input class="form-check-input" type="checkbox" name="selected_articles[]" value="<?php echo $contentId; ?>" id="article-<?php echo $contentId; ?>" <?php echo $selected ? 'checked' : ''; ?>>
								<label class="form-check-label fw-semibold" for="article-<?php echo $contentId; ?>"><?php echo htmlspecialchars((string) $article->title, ENT_QUOTES, 'UTF-8'); ?></label>
							</div>
							<div class="small text-muted mb-2"><?php echo htmlspecialchars((string) ($article->category_title ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($article->publish_up ?? $article->created ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
							<div class="row g-2">
								<div class="col-md-2"><label class="form-label small">Order</label><input type="number" class="form-control form-control-sm" name="article_ordering[<?php echo $contentId; ?>]" value="<?php echo (int) ($selected->ordering ?? $defaultOrder++); ?>"></div>
								<div class="col-md-10"><label class="form-label small">Newsletter title override</label><input class="form-control form-control-sm" name="title_override[<?php echo $contentId; ?>]" value="<?php echo htmlspecialchars((string) ($selected->title_override ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Leave blank to use the article title"></div>
								<div class="col-12"><label class="form-label small">Newsletter excerpt override</label><textarea class="form-control form-control-sm" rows="2" name="excerpt_override[<?php echo $contentId; ?>]" placeholder="Leave blank to use a shortened article intro"><?php echo htmlspecialchars((string) ($selected->excerpt_override ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
				</div></div>
			</div>

			<div class="col-12 col-xl-4">
				<div class="card mb-3"><div class="card-header"><strong>Recipients</strong></div><div class="card-body">
					<div class="form-check mb-3">
						<input type="hidden" name="include_subscribers" value="0">
						<input class="form-check-input" type="checkbox" name="include_subscribers" value="1" id="include-subscribers" <?php echo $item === null || (int) $item->include_subscribers === 1 ? 'checked' : ''; ?>>
						<label class="form-check-label" for="include-subscribers">All confirmed newsletter subscribers</label>
					</div>
					<div class="fw-semibold mb-2">Additional Joomla user groups</div>
					<div class="small text-muted mb-2">Group users still respect explicit opt-out/suppression and the configured default user-subscription policy.</div>
					<?php foreach ($this->userGroups as $group) : ?>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="group_ids[]" value="<?php echo (int) $group->id; ?>" id="group-<?php echo (int) $group->id; ?>" <?php echo in_array((int) $group->id, $this->selectedGroupIds, true) ? 'checked' : ''; ?>>
							<label class="form-check-label" for="group-<?php echo (int) $group->id; ?>"><?php echo htmlspecialchars((string) $group->title, ENT_QUOTES, 'UTF-8'); ?></label>
						</div>
					<?php endforeach; ?>
				</div></div>
				<div class="card"><div class="card-body">
					<div class="d-grid gap-2">
						<button class="btn btn-primary" type="submit" name="task" value="newsletter.save">Save draft</button>
						<button class="btn btn-outline-secondary" type="submit" name="task" value="newsletter.sendTest">Save &amp; send test to me</button>
						<button class="btn btn-success" type="submit" name="task" value="newsletter.preflight">Review recipients &amp; send…</button>
						<a class="btn btn-link" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>">Cancel</a>
					</div>
				</div></div>
			</div>
		</div>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php endif; ?>
</div>
