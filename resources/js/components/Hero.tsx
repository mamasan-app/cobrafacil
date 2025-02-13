import { Button } from '@/components/ui/button';
import Logo from '../../assets/img/logo-a.png';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';
import { Store, User } from 'lucide-react';

export const Hero = () => {
  return (
    <div className="relative bg-white px-6 pb-20 pt-2">
      <div className="absolute right-4 top-4 flex gap-2">
        <Popover>
          <PopoverTrigger asChild>
            <Button
              variant="outline"
              className="border-primary text-primary hover:bg-primary/10"
            >
              Iniciar Sesión
            </Button>
          </PopoverTrigger>
          <PopoverContent>
            <menu className="flex flex-col gap-2">
              <a
                href={route('filament.store.auth.login')}
                className="underline-none flex w-full text-gray-600 hover:text-primary"
              >
                <Store className="mr-2" />
                <span className="">Ingresar a Tienda</span>
              </a>
              <div className="h-px w-full bg-gray-200" />
              <a
                href={route('filament.app.auth.login')}
                className="underline-none flex w-full text-gray-600 hover:text-primary"
              >
                <User className="mr-2" />
                <span className="">Ingresar como Cliente</span>
              </a>
            </menu>
          </PopoverContent>
        </Popover>
        <Button className="bg-primary text-white hover:bg-primary-dark" asChild>
          <a href={route('filament.store.auth.register')}>Registrarse</a>
        </Button>
      </div>
      <div className="text-center">
        <div className="mb-0 flex justify-center">
          <img src={Logo} alt="Cobra Fácil Logo" className="h-auto w-64" />
        </div>
        <div className="mt-0">
          <h1 className="mb-4 bg-gradient-to-r from-primary to-primary-dark bg-clip-text text-5xl font-bold text-transparent">
            Cobra más fácil, más rápido y sin complicaciones
          </h1>
          <p className="mx-auto mb-8 max-w-2xl text-xl text-gray-600">
            Automatiza tus cobranzas y olvídate de las complicaciones. La
            solución perfecta para gimnasios, colegios y condominios.
          </p>
          <div className="flex justify-center gap-4">
            <Button
              asChild
              className="bg-primary px-8 py-6 text-lg text-white hover:bg-primary-dark"
              onClick={() => {}}
            >
              <a href="/tienda/registrar">Comenzar Ahora</a>
            </Button>
            {/* <Button
              variant="outline"
              className="border-primary text-primary hover:bg-primary/10 px-8 py-6 text-lg"
            >
              Solicitar Demo
            </Button> */}
          </div>
        </div>
      </div>
    </div>
  );
};
