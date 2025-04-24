<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const ContentType = 'ical';


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//example.org//iCal 4.0.3//CS
METHOD:PUBLISH
BEGIN:VEVENT
DTSTAMP:';
		echo Runtime\Filters::escapeIcal(($this->filters->date)($start, 'Ymd\\THis')) /* line %d% */;
		echo '
DTSTART;TZID=Europe/Prague:';
		echo Runtime\Filters::escapeIcal(($this->filters->date)($start, 'Ymd\\THis')) /* line %d% */;
		echo '
DTEND;TZID=Europe/Prague:';
		echo Runtime\Filters::escapeIcal(($this->filters->date)($end, 'Ymd\\THis')) /* line %d% */;
		echo '
SUMMARY;LANGUAGE=cs:';
		echo Runtime\Filters::escapeIcal($info) /* line %d% */;
		echo '
DESCRIPTION:
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR
';
	}


	public function prepare(): array
	{
		extract($this->params);

		if (empty($this->global->coreCaptured) && in_array($this->getReferenceType(), ['extends', null], true)) {
			header('Content-Type: text/calendar; charset=utf-8') /* line %d% */;
		}
		$start = '2011-06-06';
		$end = '2011-06-07';
		$info = 'Hello "hello",
World' /* line %d% */;
		return get_defined_vars();
	}
}
