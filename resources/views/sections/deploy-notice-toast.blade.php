@php
    $deployNotice = \App\Helper\DeployNotice::visibleToCurrentUser()
        ? \App\Helper\DeployNotice::current()
        : null;
@endphp
@if ($deployNotice)
<script>
    (function () {
        var noticeMessage = @json($deployNotice['message']);
        var deployedAt = @json($deployNotice['deployed_at'] ?? '');
        var storageKey = 'ryva_deploy_notice_dismissed';

        if (localStorage.getItem(storageKey) === '1') {
            return;
        }

        var text = noticeMessage;
        if (deployedAt) {
            text += ' (' + deployedAt + ')';
        }

        function showDeployNotice() {
            if (typeof Swal === 'undefined') {
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'System updated',
                html: '<p class="mb-0 text-left">' + text + '</p>',
                width: '32rem',
                showConfirmButton: true,
                confirmButtonText: 'Close',
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
            }).then(function () {
                localStorage.setItem(storageKey, '1');
            });
        }

        if (document.readyState === 'complete') {
            showDeployNotice();
        } else {
            window.addEventListener('load', showDeployNotice);
        }
    })();
</script>
@endif
