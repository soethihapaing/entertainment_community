{{--
    Continues the "Enter Point of View" transition after navigating to /feed.
    Only visible when <html> has the "intro-handoff" class (set by the head
    script in feed.blade.php); intro.js fills in the username and removes it.
--}}
<div class="intro-arrival" data-intro-arrival aria-hidden="true">
    <div class="intro-arrival__flash"></div>
    <div class="intro-arrival__frame"></div>
    <div class="intro-arrival__welcome">
        <span class="pov-avatar" data-arrival-avatar>PV</span>
        <span>
            <small>Welcome in</small>
            <span data-arrival-name>@storyteller</span>
        </span>
    </div>
</div>
