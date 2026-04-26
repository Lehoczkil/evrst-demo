import { useState } from 'react';
import { Link } from 'react-router';
import {
  Alert,
  Box,
  Checkbox,
  Group,
  Radio,
  Stack,
  Text,
  Textarea,
  TextInput,
  Title,
} from '@mantine/core';
import {
  LuArrowLeft,
  LuArrowRight,
  LuCheck,
  LuCircleAlert,
  LuCircleCheck,
  LuRocket,
} from 'react-icons/lu';
import { motion } from 'motion/react';

import { api } from '@/api';
import { HtmlTitle } from '@/components/html-title';
import { SectionButton } from '@/components/section-button';
import { useTranslation, type TranslationKey } from '@/i18n';

import classes from './join-us.module.css';

const EDUCATION_OPTIONS = ['BSc', 'MSc', 'PhD'] as const;
const LANGUAGE_OPTIONS = ['Hungarian', 'English', 'German'] as const;
const DEPARTMENT_OPTIONS = [
  'Marketing & Design',
  'Electronics & Software Development',
  'Propulsion',
  'Structure & Aerodynamics',
  'Management',
] as const;

const PLUS_KEYS: TranslationKey[] = [
  'join.plus.english',
  'join.plus.knowledge',
  'join.plus.hours',
  'join.plus.inPerson',
  'join.plus.tools',
  'join.plus.tdk',
];

interface FormState {
  email: string;
  name: string;
  university: string;
  education: string;
  faculty: string;
  why: string;
  hours: string;
  languages: string[];
  otherLanguage: string;
  department: string;
  tasks: string;
  skills: string;
}

const initialState: FormState = {
  email: '',
  name: '',
  university: '',
  education: '',
  faculty: '',
  why: '',
  hours: '',
  languages: [],
  otherLanguage: '',
  department: '',
  tasks: '',
  skills: '',
};

type Status = 'idle' | 'submitting' | 'success' | 'error';

interface SectionProps {
  index: number;
  title: string;
  children: React.ReactNode;
}

function FormSection({ index, title, children }: SectionProps) {
  const number = `0${index + 1}`.slice(-2);
  return (
    <motion.section
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, amount: 0.1 }}
      transition={{ duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
      className={classes.formCard}
    >
      <div className={classes.sectionHeader}>
        <span className={classes.sectionNumber}>{number}</span>
        <h2 className={classes.sectionTitle}>{title}</h2>
      </div>
      {children}
    </motion.section>
  );
}

export function JoinUsPage() {
  const { t } = useTranslation();
  const [values, setValues] = useState<FormState>(initialState);
  const [status, setStatus] = useState<Status>('idle');

  const update = <K extends keyof FormState>(key: K, value: FormState[K]) => {
    setValues((prev) => ({ ...prev, [key]: value }));
  };

  const handleSubmit = async () => {
    setStatus('submitting');

    const otherLanguage = values.otherLanguage.trim();
    const languages = [
      ...values.languages,
      ...(otherLanguage ? [`Other: ${otherLanguage}`] : []),
    ];

    try {
      await api.request({
        url: '/member-applications',
        method: 'post',
        body: {
          email: values.email,
          name: values.name,
          university: values.university,
          education: values.education,
          faculty: values.faculty,
          why: values.why,
          hours: values.hours,
          languages,
          department: values.department,
          tasks: values.tasks,
          skills: values.skills,
        },
      });
      setStatus('success');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch {
      setStatus('error');
    }
  };

  const reset = () => {
    setValues(initialState);
    setStatus('idle');
  };

  if (status === 'success') {
    return (
      <Box className={classes.page} py='xl'>
        <HtmlTitle>{t('join.title')}</HtmlTitle>
        <Stack gap='lg'>
          <Group>
            <SectionButton
              component={Link}
              to='/'
              leftSection={<LuArrowLeft />}
            >
              {t('button.home')}
            </SectionButton>
          </Group>
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
            className={classes.successCard}
          >
            <Stack gap='lg' align='center'>
              <span className={classes.successIcon}>
                <LuCircleCheck size={36} />
              </span>
              <Title order={2} fz='clamp(28px, 5vw, 40px)' tt='uppercase'>
                {t('join.success.title')}
              </Title>
              <Text fz='16px' c='dimmed' lh='1.6' maw={520}>
                {t('join.success.body')}
              </Text>
              <SectionButton onClick={reset} rightSection={<LuArrowRight />}>
                {t('join.success.again')}
              </SectionButton>
            </Stack>
          </motion.div>
        </Stack>
      </Box>
    );
  }

  const submitting = status === 'submitting';

  return (
    <Box className={classes.page} py='xl'>
      <HtmlTitle>{t('join.title')}</HtmlTitle>

      <Stack gap='xl'>
        <Group>
          <SectionButton
            component={Link}
            to='/'
            leftSection={<LuArrowLeft />}
          >
            {t('button.home')}
          </SectionButton>
        </Group>

        <motion.div
          initial={{ opacity: 0, y: 24 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
          className={classes.hero}
        >
          <div className={classes.heroGrid}>
            <Stack gap='lg' style={{ minWidth: 0 }}>
              <span className={classes.kicker}>
                <LuRocket size={14} />
                {t('join.tagline')}
              </span>
              <Title
                order={1}
                tt='uppercase'
                c='primary'
                lh='0.96'
                fz='clamp(40px, 8vw, 84px)'
                style={{ letterSpacing: '-0.01em', margin: 0 }}
              >
                {t('join.title')}
              </Title>
              <Text fz='17px' c='dimmed' lh='1.6' className={classes.intro}>
                {t('join.intro')}
              </Text>
            </Stack>
            <div className={classes.plusCard}>
              <div className={classes.plusHeading}>
                {t('join.plus.heading')}
              </div>
              <ul className={classes.plusList}>
                {PLUS_KEYS.map((key) => (
                  <li key={key} className={classes.plusItem}>
                    <span className={classes.plusBullet}>
                      <LuCheck size={11} strokeWidth={3} />
                    </span>
                    <span>{t(key)}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </motion.div>

        {status === 'error' && (
          <Alert
            color='red'
            icon={<LuCircleAlert />}
            radius='md'
            variant='light'
          >
            {t('join.error')}
          </Alert>
        )}

        <form
          onSubmit={(event) => {
            event.preventDefault();
            void handleSubmit();
          }}
          noValidate
        >
          <Stack gap='xl'>
            <FormSection index={0} title={t('join.section.about')}>
              <Stack gap='md'>
                <TextInput
                  label={t('join.email')}
                  type='email'
                  required
                  size='md'
                  value={values.email}
                  onChange={(e) => update('email', e.currentTarget.value)}
                  autoComplete='email'
                />
                <div className={`${classes.row} ${classes.row2}`}>
                  <TextInput
                    label={t('join.name')}
                    required
                    size='md'
                    value={values.name}
                    onChange={(e) => update('name', e.currentTarget.value)}
                    autoComplete='name'
                  />
                  <TextInput
                    label={t('join.university')}
                    required
                    size='md'
                    value={values.university}
                    onChange={(e) => update('university', e.currentTarget.value)}
                  />
                </div>
                <Radio.Group
                  label={t('join.education')}
                  required
                  value={values.education}
                  onChange={(value) => update('education', value)}
                >
                  <div
                    className={`${classes.optionGrid} ${classes.optionGridCols}`}
                  >
                    {EDUCATION_OPTIONS.map((option) => (
                      <label
                        key={option}
                        className={classes.optionPill}
                        data-checked={values.education === option}
                      >
                        <Radio value={option} label={option} />
                      </label>
                    ))}
                  </div>
                </Radio.Group>
                <TextInput
                  label={t('join.faculty')}
                  required
                  size='md'
                  value={values.faculty}
                  onChange={(e) => update('faculty', e.currentTarget.value)}
                />
              </Stack>
            </FormSection>

            <FormSection index={1} title={t('join.section.availability')}>
              <Stack gap='md'>
                <Textarea
                  label={t('join.why')}
                  required
                  autosize
                  minRows={3}
                  size='md'
                  value={values.why}
                  onChange={(e) => update('why', e.currentTarget.value)}
                />
                <TextInput
                  label={t('join.hours')}
                  description={t('join.hoursHelp')}
                  required
                  size='md'
                  value={values.hours}
                  onChange={(e) => update('hours', e.currentTarget.value)}
                />
                <Checkbox.Group
                  label={t('join.languages')}
                  required
                  value={values.languages}
                  onChange={(value) => update('languages', value)}
                >
                  <div
                    className={`${classes.optionGrid} ${classes.optionGridCols}`}
                  >
                    {LANGUAGE_OPTIONS.map((option) => (
                      <label
                        key={option}
                        className={classes.optionPill}
                        data-checked={values.languages.includes(option)}
                      >
                        <Checkbox value={option} label={option} />
                      </label>
                    ))}
                  </div>
                </Checkbox.Group>
                <TextInput
                  label={t('join.languages.other')}
                  placeholder={t('join.languages.otherPlaceholder')}
                  size='md'
                  value={values.otherLanguage}
                  onChange={(e) =>
                    update('otherLanguage', e.currentTarget.value)
                  }
                />
              </Stack>
            </FormSection>

            <FormSection index={2} title={t('join.section.contribution')}>
              <Stack gap='md'>
                <Radio.Group
                  label={t('join.department')}
                  required
                  value={values.department}
                  onChange={(value) => update('department', value)}
                >
                  <div className={classes.optionGrid}>
                    {DEPARTMENT_OPTIONS.map((option) => (
                      <label
                        key={option}
                        className={classes.optionPill}
                        data-checked={values.department === option}
                      >
                        <Radio value={option} label={option} />
                      </label>
                    ))}
                  </div>
                </Radio.Group>
                <Textarea
                  label={t('join.tasks')}
                  required
                  autosize
                  minRows={3}
                  size='md'
                  value={values.tasks}
                  onChange={(e) => update('tasks', e.currentTarget.value)}
                />
                <Textarea
                  label={t('join.skills')}
                  required
                  autosize
                  minRows={3}
                  size='md'
                  value={values.skills}
                  onChange={(e) => update('skills', e.currentTarget.value)}
                />
              </Stack>
            </FormSection>

            <div className={classes.submitRow}>
              <SectionButton
                component={Link}
                to='/'
                leftSection={<LuArrowLeft />}
              >
                {t('button.home')}
              </SectionButton>
              <SectionButton
                type='submit'
                rightSection={<LuArrowRight />}
                loading={submitting}
                disabled={submitting}
              >
                {submitting ? t('join.submitting') : t('join.submit')}
              </SectionButton>
            </div>
          </Stack>
        </form>
      </Stack>
    </Box>
  );
}

export default JoinUsPage;
