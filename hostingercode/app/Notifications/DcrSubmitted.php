<?php

namespace App\Notifications;

use App\Models\DcrReport;
use App\Models\EmailNotificationSetting;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class DcrSubmitted extends BaseNotification
{
    private $dcrReport;
    private $emailSetting;

    public function __construct(DcrReport $dcrReport)
    {
        $this->dcrReport = $dcrReport;
        $this->company = $dcrReport->company;
        if ($this->company) {
            $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'dcr-submitted')->first();
        }
    }

    public function via($notifiable)
    {
        $via = ['database'];

        if (is_null($this->company)) {
            array_push($via, 'mail');
            return $via;
        }

        if ($this->emailSetting && $notifiable) {
            if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
                array_push($via, 'mail');
            }
            if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting && $this->company->slackSetting->status == 'active') {
                $this->slackUserNameCheck($notifiable) ? array_push($via, 'slack') : null;
            }
            if ($this->emailSetting->send_push == 'yes' && push_setting()->status == 'active') {
                array_push($via, OneSignalChannel::class);
            }
        }

        return $via;
    }

    public function toMail($notifiable)
    {
        $build = parent::build($notifiable);
        $url = route('dcr-management.index');
        $url = getDomainSpecificUrl($url, $this->company);
        $submitterName = $this->dcrReport->user ? $this->dcrReport->user->name : __('Employee');
        $reportDate = $this->dcrReport->report_date ? \Carbon\Carbon::parse($this->dcrReport->report_date)->format(company()->date_format) : '-';
        $content = $submitterName . ' ' . __('has submitted DCR for date') . ' ' . $reportDate . '.';

        $build
            ->subject(__('DCR submitted') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('View DCR'),
                'notifiableName' => $notifiable->name
            ]);

        parent::resetLocale();
        return $build;
    }

    public function toArray($notifiable)
    {
        return [
            'id' => $this->dcrReport->id,
            'user_id' => $notifiable->id,
            'report_date' => $this->dcrReport->report_date,
            'submitted_by' => $this->dcrReport->user_id,
            'message' => __('DCR submitted for') . ' ' . ($this->dcrReport->report_date ?? '-'),
        ];
    }

    public function toSlack($notifiable)
    {
        if ($this->slackUserNameCheck($notifiable)) {
            return $this->slackBuild($notifiable)
                ->content(__('DCR submitted for') . ' ' . ($this->dcrReport->report_date ?? '-'));
        }
        return $this->slackRedirectMessage('DCR submitted', $notifiable);
    }

    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject(__('DCR submitted'))
            ->setBody(__('DCR submitted for') . ' ' . ($this->dcrReport->report_date ?? '-'));
    }
}
