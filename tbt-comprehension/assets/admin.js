(function () {
	'use strict';

	var TOPIC_COLORS = {
		learn_english: '#660000',
		general_interest: '#663366',
		personal_development: '#006600'
	};

	// CSS injected into the generated HTML when at least one tooltip is present.
	// Scoped under .tbt-tt-wrap so it cannot affect anything else on the page.
	var TOOLTIP_STYLE = [
		'<style>',
		'.tbt-tt-wrap{position:relative;display:inline-block;margin-left:6px;vertical-align:middle;line-height:1;}',
		'.tbt-tt-wrap .tbt-tt-icon{display:inline-block;width:18px;height:18px;line-height:18px;border-radius:50%;background:#0856c9;color:#fff;font:bold 12px/18px Arial,sans-serif;text-align:center;cursor:help;user-select:none;}',
		'.tbt-tt-wrap .tbt-tt-bubble{visibility:hidden;opacity:0;transition:opacity 120ms ease-in;position:absolute;z-index:9999;left:50%;transform:translateX(-50%);bottom:calc(100% + 10px);min-width:220px;max-width:320px;background:#1f2d3d;color:#fff;padding:10px 12px;border-radius:6px;font:14px/1.45 Arial,sans-serif;text-align:left;box-shadow:0 4px 14px rgba(0,0,0,0.18);}',
		'.tbt-tt-wrap .tbt-tt-bubble::after{content:"";position:absolute;top:100%;left:50%;transform:translateX(-50%);border:6px solid transparent;border-top-color:#1f2d3d;}',
		'.tbt-tt-wrap:hover .tbt-tt-bubble,.tbt-tt-wrap:focus-within .tbt-tt-bubble{visibility:visible;opacity:1;}',
		'</style>'
	].join('');

	function $(id) { return document.getElementById(id); }

	function getSelectedColor() {
		return TOPIC_COLORS[$('tbt-topic').value] || TOPIC_COLORS.learn_english;
	}

	function updateSwatch() {
		var color = getSelectedColor();
		$('tbt-swatch').style.background = color;
		$('tbt-hex-label').textContent = color;
	}

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function applyInlineFormatting(text) {
		var safe = escapeHtml(text);
		safe = safe.replace(/\*([^*]+)\*/g, '<i>$1</i>');
		safe = safe.replace(/_([^_]+)_/g, '<i>$1</i>');
		return safe;
	}

	// Normalize "0:13", "00:13", "12:05" -> canonical "M:SS" form for matching.
	function normalizeTime(t) {
		var m = String(t).trim().match(/^(\d{1,3}):(\d{2})$/);
		if (!m) return null;
		return parseInt(m[1], 10) + ':' + m[2];
	}

	function parseInput(raw) {
		var lines = raw.split('\n').map(function (l) { return l.trim(); }).filter(function (l) { return l.length > 0; });
		var rows = [];
		var re = /^(\d{1,3}:\d{2})\s+(.+)$/;
		for (var i = 0; i < lines.length; i++) {
			var m = lines[i].match(re);
			if (m) rows.push({ time: m[1], question: m[2].trim() });
		}
		return rows;
	}

	// Simple CSV line parser supporting double-quoted fields with escaped quotes.
	function parseCsvLine(line) {
		var out = [];
		var cur = '';
		var inQuotes = false;
		for (var i = 0; i < line.length; i++) {
			var ch = line.charAt(i);
			if (inQuotes) {
				if (ch === '"') {
					if (line.charAt(i + 1) === '"') { cur += '"'; i++; }
					else { inQuotes = false; }
				} else {
					cur += ch;
				}
			} else {
				if (ch === ',') { out.push(cur); cur = ''; }
				else if (ch === '"' && cur.length === 0) { inQuotes = true; }
				else { cur += ch; }
			}
		}
		out.push(cur);
		return out;
	}

	function parseQa(raw) {
		var lines = raw.split(/\r?\n/).filter(function (l) { return l.trim().length > 0; });
		if (lines.length === 0) return { rows: [], map: {} };

		// Skip a header row only if its first column is not a valid timestamp.
		// (Checking for words like "question" in the line is too aggressive —
		// real question text often contains the word "question".)
		var firstCol = lines[0].split(/\t|,/)[0].trim();
		if (!normalizeTime(firstCol)) {
			lines.shift();
		}

		var rows = [];
		var map = {};
		for (var i = 0; i < lines.length; i++) {
			var line = lines[i];
			var parts, delim;
			if (line.indexOf('\t') !== -1) {
				delim = '\t';
				parts = line.split('\t');
			} else {
				delim = ',';
				parts = parseCsvLine(line);
			}
			if (parts.length < 2) continue;
			var key = normalizeTime(parts[0]);
			if (!key) continue;
			var question = (parts[1] || '').trim();
			// Rejoin any extra columns so an answer that contained the delimiter
			// is preserved intact (e.g. a CSV answer with unquoted commas).
			var answer = parts.length >= 3 ? parts.slice(2).join(delim).trim() : '';
			rows.push({ time: key, question: question, answer: answer });
			if (answer) map[key] = answer;
		}
		return { rows: rows, map: map };
	}

	function wrapQuestion(questionHtml) {
		// Preserve the original behavior of wrapping each chunk in
		// <span style="font-weight: 400;"> so pasted output renders consistently
		// in WordPress themes that bold table cells by default.
		var parts = questionHtml.split(/(<i>[^<]+<\/i>)/g);
		return parts.map(function (p) {
			if (p.startsWith('<i>')) {
				var inner = p.replace(/<i>([^<]+)<\/i>/, '$1');
				return '<i><span style="font-weight: 400;">' + inner + '</span></i>';
			} else if (p.length > 0) {
				return '<span style="font-weight: 400;">' + p + '</span>';
			}
			return '';
		}).join('');
	}

	function buildTooltipHtml(answer) {
		var safe = escapeHtml(answer);
		return [
			'<span class="tbt-tt-wrap" tabindex="0" aria-label="Hint">',
			'<span class="tbt-tt-icon" aria-hidden="true">?</span>',
			'<span class="tbt-tt-bubble" role="tooltip">', safe, '</span>',
			'</span>'
		].join('');
	}

	function buildHtml(rows, qaMap, color) {
		if (rows.length === 0) return { html: '', hintCount: 0 };

		var hintCount = 0;
		var body = '<table class="qa-table">\n<tbody>\n';
		rows.forEach(function (row, i) {
			var num = i + 1;
			var questionHtml = applyInlineFormatting(row.question);
			var wrapped = wrapQuestion(questionHtml);

			var key = normalizeTime(row.time);
			var tooltip = '';
			if (key && qaMap[key]) {
				tooltip = buildTooltipHtml(qaMap[key]);
				hintCount++;
			}

			body += '<tr>\n';
			body += '<td class="col-num"><span class="circle_pd" style="background-color: ' + color + ';">' + num + '</span></td>\n';
			body += '<td class="col-time">' + escapeHtml(row.time) + '</td>\n';
			body += '<td class="col-q">' + wrapped + tooltip + '</td>\n';
			body += '</tr>\n';
		});
		body += '</tbody>\n</table>';

		// Only prepend the scoped <style> block when at least one tooltip is rendered,
		// so the no-hint output stays byte-for-byte compatible with the original generator.
		var html = hintCount > 0 ? (TOOLTIP_STYLE + '\n' + body) : body;
		return { html: html, hintCount: hintCount };
	}

	function setStatus(text, type) {
		var el = $('tbt-status');
		el.textContent = text;
		el.className = 'tbt-status ' + (type || '');
	}

	function generate() {
		var raw = $('tbt-input').value;
		var qaRaw = $('tbt-qa').value;
		var rows = parseInput(raw);
		var qa = parseQa(qaRaw);
		var color = getSelectedColor();

		// If the Questions box is empty but the Q&A box has data, use the Q&A
		// rows as the source of truth — the timestamp/question columns build
		// the table and the answer column becomes the hint.
		var sourceUsed = 'questions';
		if (rows.length === 0 && qa.rows.length > 0) {
			rows = qa.rows.map(function (r) { return { time: r.time, question: r.question }; });
			sourceUsed = 'qa';
		}

		if (rows.length === 0) {
			var msgEmpty = qaRaw.trim().length > 0
				? 'No valid rows found. Paste 3 columns (timestamp, question, answer) — tabs from Excel or commas.'
				: 'No valid lines found. Format must be: <code>0:18 Question text</code>';
			$('tbt-preview').innerHTML = '<em style="color:#c0392b;">' + msgEmpty + '</em>';
			$('tbt-html-output').textContent = '';
			setStatus('', '');
			return;
		}

		var result = buildHtml(rows, qa.map, color);
		$('tbt-preview').innerHTML = result.html;
		$('tbt-html-output').textContent = result.html;

		var msg = '✓ ' + rows.length + ' question' + (rows.length === 1 ? '' : 's') + ' generated';
		if (result.hintCount > 0) {
			msg += ', ' + result.hintCount + ' with hint' + (result.hintCount === 1 ? '' : 's');
		}
		if (sourceUsed === 'qa') {
			msg += ' (from Hint Q&A box)';
		}
		// Warn if hints were provided but none matched a question row.
		var qaCount = Object.keys(qa.map).length;
		if (sourceUsed === 'questions' && qaCount > 0 && result.hintCount === 0) {
			setStatus(msg + ' — hint rows provided but none matched any timestamp', 'err');
		} else {
			setStatus(msg, 'ok');
		}
	}

	function fallbackCopy(text) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.top = '0';
		ta.style.left = '0';
		ta.style.opacity = '0';
		ta.setAttribute('readonly', '');
		document.body.appendChild(ta);
		ta.focus();
		ta.select();
		ta.setSelectionRange(0, text.length);
		var ok = false;
		try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
		document.body.removeChild(ta);
		if (ok) setStatus('✓ HTML copied to clipboard', 'info');
		else setStatus('Copy failed — please select the HTML manually and copy.', 'err');
	}

	function copyHtml() {
		var text = $('tbt-html-output').textContent;
		if (!text || text.trim().length === 0 || text.indexOf('HTML will appear') !== -1) {
			setStatus('Generate the table first.', 'err');
			return;
		}
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(
				function () { setStatus('✓ HTML copied to clipboard', 'info'); },
				function () { fallbackCopy(text); }
			);
		} else {
			fallbackCopy(text);
		}
	}

	function loadExample() {
		$('tbt-input').value =
			'0:18 Is empathy a commonly known term?\n' +
			'0:39 What are we going to look into in this video series?\n' +
			'0:55 What is the definition of the word "empathy"?\n' +
			'1:07 What does it mean to emotionally resonate with someone?';
		$('tbt-qa').value =
			'timestamp\tquestion\tanswer\n' +
			'0:18\tIs empathy a commonly known term?\tNot really — most people use the word loosely without grasping its full meaning.\n' +
			'0:55\tWhat is the definition of the word "empathy"?\tThe ability to understand and share another person\'s feelings.';
		generate();
	}

	document.addEventListener('DOMContentLoaded', function () {
		updateSwatch();
		$('tbt-topic').addEventListener('change', function () { updateSwatch(); generate(); });
		$('tbt-generate').addEventListener('click', generate);
		$('tbt-copy').addEventListener('click', copyHtml);
		$('tbt-example').addEventListener('click', loadExample);
	});
})();
