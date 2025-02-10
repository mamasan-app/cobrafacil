import {
  Mail,
  Phone,
  MessageSquare,
  Facebook,
  InstagramIcon,
  Linkedin,
  XIcon,
} from 'lucide-react';

export const ContactForm = () => {
  return (
    <div id="contact" className="bg-gray-50 py-20">
      <div className="container mx-auto max-w-4xl px-6">
        <h2 className="mb-16 text-center text-3xl font-bold">Contáctanos</h2>
        <div className="grid gap-12 md:grid-cols-3">
          <div>
            <h3 className="mb-6 text-xl font-semibold">
              Información de contacto
            </h3>
            <div className="space-y-6">
              <div className="flex items-center gap-4">
                <Mail className="h-5 w-5 text-primary" />
                <span>soporte@cobrafacil.com</span>
              </div>
              {/* <div className="flex items-center gap-4">
                <Phone className="h-5 w-5 text-primary" />
                <span>+58 (424) 123-4567</span>
              </div> */}
              {/* <div className="flex items-center gap-4">
                <MessageSquare className="text-primary h-5 w-5" />
                <span>Chat en vivo disponible</span>
              </div> */}
            </div>
          </div>

          <div>
            <h3 className="mb-6 text-xl font-semibold">Horario</h3>
            <div className="space-y-4">
              <p className="text-gray-600">
                Lunes a Viernes: 8:00 AM - 6:00 PM
              </p>
              <p className="text-gray-600">Sábados: 9:00 AM - 1:00 PM</p>
            </div>
          </div>

          <div>
            <h3 className="mb-6 text-xl font-semibold">Síguenos</h3>
            <div className="flex gap-6">
              <a
                href="#"
                className="text-primary transition-colors hover:text-primary-dark"
                aria-label="Facebook"
              >
                <Facebook className="h-6 w-6" />
              </a>
              <a
                href="#"
                className="text-primary transition-colors hover:text-primary-dark"
                aria-label="Instagram"
              >
                <InstagramIcon className="h-6 w-6" />
              </a>
              <a
                href="#"
                className="text-primary transition-colors hover:text-primary-dark"
                aria-label="Twitter"
              >
                <XIcon className="h-6 w-6" />
              </a>
              <a
                href="#"
                className="text-primary transition-colors hover:text-primary-dark"
                aria-label="LinkedIn"
              >
                <Linkedin className="h-6 w-6" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
