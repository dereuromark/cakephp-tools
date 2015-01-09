## Useful Features

### Debugging
Use `Configure::write('App.monitorHeader', true);` to assert, that all controller actions don't (accidently) sent any headers prior
to the actual response->send() call. It will throw an exception in debug mode, and trigger an error in productive mode.

Make sure your AppController extends the Tools plugin MyController.
