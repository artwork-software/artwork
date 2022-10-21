@component('mail::message', ['user' => $user])

    <h1 style="margin: 5rem 0 1rem 0; font-size: 2rem;">
        Lorem ipsum dolor sit amet
    </h1>

    <p style="font-weight: 300; margin-bottom: 3em;">
        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
        Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
    </p>

    <h2 style="font-weight: 300; color: black; font-size: 1.25rem;">
        In mollis nunc sed id semper risus in
    </h2>

    <p style="font-weight: 300; margin-bottom: 3em;">
        Aliquam eleifend mi in nulla posuere sollicitudin aliquam ultrices. Viverra maecenas accumsan lacus vel.
        Senectus et netus et malesuada. Morbi non arcu risus quis varius quam quisque. Id consectetur purus ut faucibus
        pulvinar elementum. Eget egestas purus viverra accumsan in nisl nisi scelerisque. Tincidunt tortor aliquam nulla
        facilisi. Elit sed vulputate mi sit amet. A condimentum vitae sapien pellentesque habitant morbi. Id eu nisl
        nunc mi ipsum faucibus vitae aliquet nec. Urna cursus eget nunc scelerisque viverra mauris in aliquam. Et ligula
        ullamcorper malesuada proin libero nunc consequat interdum varius. Consectetur a erat nam at lectus urna.
    </p>

    @component('mail::button', ['url' => url("/users/invitations/accept?token=$token&email={$invitation->email}")])
        Einladung akzeptieren
    @endcomponent
@endcomponent
