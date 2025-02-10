import { Hero } from '@/components/Hero';
import { Benefits } from '@/components/Benefits';
import { HowItWorks } from '@/components/HowItWorks';
import { Services } from '@/components/Services';
import { Pricing } from '@/components/Pricing';
import { ContactForm } from '@/components/ContactForm';

export default function WelcomePage() {
  return (
    <div className="min-h-screen bg-white">
      <Hero />
      <Benefits />
      <HowItWorks />
      <Services />
      {/* <Pricing /> */}
      <ContactForm />
    </div>
  );
}
