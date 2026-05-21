@php
    $deployNotice = \App\Helper\DeployNotice::current();
@endphp
@if ($deployNotice)
<script>
    $(function () {
        var noticeId = @json($deployNotice['id']);
        var noticeMessage = @json($deployNotice['message']);
        var deployedAt = @json($deployNotice['deployed_at'] ?? '');
        var storageKey = 'ryva_deploy_notice_seen';

        if (localStorage.getItem(storageKey) === noticeId) {
            return;
        }

        var text = noticeMessage;
        if (deployedAt) {
            text += ' (' + deployedAt + ')';
        }

        Swal.fire({
            icon: 'success',
            title: 'System updated',
            text: text,
            toast: true,
            position: 'top-end',
            timer: 10000,
            timerProgressBar: true,
            showConfirmButton: true,
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'btn btn-primary',
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation',
            },
        });

        localStorage.setItem(storageKey, noticeId);
    });
</script>
@endif
