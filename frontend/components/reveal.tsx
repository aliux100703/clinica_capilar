"use client";
import { motion, useReducedMotion } from "motion/react";
import type { ReactNode } from "react";

export function Reveal({ children, className = "" }: { children: ReactNode; className?: string }) {
  const reduced = useReducedMotion();
  return <motion.div className={className} initial={false} whileInView={reduced ? undefined : { y: [14, 0] }} viewport={{ once: true, amount: 0.15 }} transition={{ duration: 0.6, ease: "easeOut" }}>{children}</motion.div>;
}
